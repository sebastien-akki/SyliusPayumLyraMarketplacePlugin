<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Handler;

use Akki\SyliusPayumLyraMarketplacePlugin\Api\Api;
use Akki\SyliusPayumLyraMarketplacePlugin\Entity\Refund\RefundPaymentInterface;
use Akki\SyliusPayumLyraMarketplacePlugin\Service\LyraMarketplaceService;
use Doctrine\ORM\EntityManagerInterface;
use Swagger\Client\Configuration;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\RefundPlugin\Entity\CreditMemo;
use Sylius\RefundPlugin\Event\RefundPaymentGenerated;
use Sylius\RefundPlugin\Repository\CreditMemoRepositoryInterface;

class RefundPaymentHandler
{
    public function __construct(
        private CreditMemoRepositoryInterface $creditMemoRepository,
        private OrderRepositoryInterface $orderRepository,
        private RepositoryInterface $paymentMethodRepository,
        private RepositoryInterface $refundPaymentRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(RefundPaymentGenerated $refundPaymentGenerated): void
    {
        /** @var RefundPaymentInterface $refundPayment */
        $refundPayment = $this->refundPaymentRepository->find($refundPaymentGenerated->id());
        if ($refundPayment === null) {
            return;
        }

        $paymentMethod = $this->paymentMethodRepository->find($refundPaymentGenerated->paymentMethodId());
        if ($paymentMethod?->getCode() !== 'lyra_market_place') {
            return;
        }

        $creditMemo = $this->getLastCreditMemo($refundPaymentGenerated->orderNumber(), $refundPaymentGenerated->amount());
        if ($creditMemo === null) {
            return;
        }

        $config = $paymentMethod->getGatewayConfig()->getConfig();

        $marketplaceConfiguration = new Configuration();
        $marketplaceConfiguration
            ->setUsername((string)$config['username'])
            ->setPassword((string)$config['password'])
            ->setHost(Api::getUrlFromEndpoint((string)$config['ctx_mode']));

        $marketplaceUUID = (string)$config['marketplace_uuid'];
        $lyraMarketplaceSellerUUID = (string)$config['lyra_marketplace_seller_uuid'];
        $marketplaceService = new LyraMarketplaceService($this->entityManager, $marketplaceConfiguration, $marketplaceUUID, $lyraMarketplaceSellerUUID);

        $refund = $marketplaceService->refundCreditMemo($creditMemo);
        if (!$refund) {
            throw new \Exception('Impossible de créer la demande de remboursement auprès de Lyra Marketplace');
        }

        $refundPayment->setLyraRefundUuid($refund->getUuid());
        $this->entityManager->flush();
    }

    private function getLastCreditMemo(string $orderNumber, int $amount): ?CreditMemo
    {
        $order = $this->orderRepository->findOneBy(['number' => $orderNumber]);
        if ($order === null) {
            return null;
        }

        $creditMemos = $this->creditMemoRepository->findBy([
            'order' => $order->getId(),
            'total' => $amount,
        ], ['id' => 'DESC']);

        return array_shift($creditMemos);
    }
}
