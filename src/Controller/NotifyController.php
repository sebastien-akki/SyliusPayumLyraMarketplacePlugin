<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Controller;


use Akki\SyliusPayumLyraMarketplacePlugin\Api\Api;
use Exception;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Request\Notify;
use Swagger\Client\ApiException;
use Swagger\Client\Model\OrderSerializer;
use Swagger\Client\Model\Refund;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\Model\Payment;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NotifyController extends PayumController
{

    /** @var ContainerInterface */
    protected $container;

    /** @var RepositoryInterface $paymentMethodRepository */
    private $paymentMethodRepository;

    /** @var OrderRepositoryInterface $orderRepository */
    private $orderRepository;

    /**
     * @param ContainerInterface $container
     * @param RepositoryInterface $paymentMethodRepository
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ContainerInterface $container,
        RepositoryInterface $paymentMethodRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->container = $container;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return Response
     *
     * @throws ApiException
     * @throws Exception
     */
    public function doOrderAction(Request $request): Response
    {
        $body = file_get_contents('php://input');
        $json = $this->fromJson($body);

        $lyraMarketplacePaymentMethod = $this->paymentMethodRepository->findOneBy(['code' => 'lyra_market_place']);

        $api = new Api();
        $api->setConfig($lyraMarketplacePaymentMethod->getGatewayConfig()->getConfig());
        $api->setContainer($this->container);

        /** @var ?Order $order  */
        $order = $this->orderRepository->findOneBy(['lyraOrderUuid' => $json['order']]);

        if (!($order instanceof Order)){
            return new Response();
        }

        $orderInfos = $api->retrieveOrder($order->getLyraOrderUuid());

        if ($orderInfos instanceof OrderSerializer){
            if (in_array($orderInfos->getStatus(), [OrderSerializer::STATUS_PENDING, OrderSerializer::STATUS_SUCCEEDED], true)) {
                /** @var Payment $payment */
                $payment = $order->getLastPayment();

                /** @var PaymentMethodInterface $paymentMethod */
                $paymentMethod = $payment->getMethod();

                /** @var GatewayConfigInterface $gatewayConfig */
                $gatewayConfig = $paymentMethod->getGatewayConfig();

                // Execute notify & status actions.
                $gateway = $this->getPayum()->getGateway($gatewayConfig->getGatewayName());

                $gateway->execute(new Notify($payment));
            }
        }

        return new Response();
    }

    /**
     * @return Response
     *
     * @throws ApiException
     * @throws Exception
     */
    public function doRefundAction(Request $request): Response
    {
        $body = file_get_contents('php://input');
        $json = $this->fromJson($body);

        $lyraMarketplacePaymentMethod = $this->paymentMethodRepository->findOneBy(['code' => 'lyra_market_place']);

        $api = new Api();
        $api->setConfig($lyraMarketplacePaymentMethod->getGatewayConfig()->getConfig());
        $api->setContainer($this->container);

        $refund = $api->retrieveRefund($json['refund']);

        /** @var ?Order $order  */
        $order = $this->orderRepository->findOneBy(['lyraOrderUuid' => $refund->getOrder()]);

        if (!($order instanceof Order)){
            return new Response();
        }

        if ($refund instanceof Refund){
            if (in_array($refund->getStatus(), [Refund::STATUS_SUCCEEDED, OrderSerializer::STATUS_FAILED, OrderSerializer::STATUS_CANCELLED], true)) {
                /** @var Payment $payment */
                $payment = $order->getLastPayment();

                /** @var PaymentMethodInterface $paymentMethod */
                $paymentMethod = $payment->getMethod();

                /** @var GatewayConfigInterface $gatewayConfig */
                $gatewayConfig = $paymentMethod->getGatewayConfig();

                // Execute notify & status actions.
                $gateway = $this->getPayum()->getGateway($gatewayConfig->getGatewayName());

                $gateway->execute(new Notify($payment));
            }
        }

        return new Response();
    }

    /**
     * @param $json
     *
     * @return array|mixed
     *
     * @throws Exception.
     */
    protected function fromJson($json)
    {
        if (!$json) {
            $json = [];
        }

        if (!is_array($json)) {
            if (is_object($json)) {
                $json = (array) $json;
            } elseif (is_string($json)) {
                $json = json_decode(trim($json) ? $json : '{}', true, 512, JSON_THROW_ON_ERROR);
            } else {
                throw new \RuntimeException("JSON must be a string, an array or an object ('" . gettype($json) . "' provided).");
            }
        }

        return $json;
    }
}
