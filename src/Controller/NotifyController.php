<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Controller;


use Akki\SyliusPayumLyraMarketplacePlugin\Api\Api;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\NotifyRefund;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\NotifyToken;
use Exception;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Request\Notify;
use SM\Factory\FactoryInterface as StateMachineFactoryInterface;
use Swagger\Client\ApiException;
use Swagger\Client\Model\OrderRegister;
use Swagger\Client\Model\OrderSerializer;
use Swagger\Client\Model\Refund;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\Model\Payment;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\RefundPlugin\StateResolver\RefundPaymentCompletedStateApplierInterface;
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

    /** @var RepositoryInterface $refundPaymentRepository */
    private $refundPaymentRepository;

    /** @var RefundPaymentCompletedStateApplierInterface $refundPaymentCompletedStateApplier */
    private $refundPaymentCompletedStateApplier;

    /** @var StateMachineFactoryInterface $stateMachineFactory */
    private $stateMachineFactory;

    /**
     * @param ContainerInterface                          $container
     * @param RepositoryInterface                         $paymentMethodRepository
     * @param OrderRepositoryInterface                    $orderRepository
     * @param RepositoryInterface                         $refundPaymentRepository
     * @param RefundPaymentCompletedStateApplierInterface $refundPaymentCompletedStateApplier
     * @param StateMachineFactoryInterface                $stateMachineFactory
     */
    public function __construct(
        ContainerInterface $container,
        RepositoryInterface $paymentMethodRepository,
        OrderRepositoryInterface $orderRepository,
        RepositoryInterface $refundPaymentRepository,
        RefundPaymentCompletedStateApplierInterface $refundPaymentCompletedStateApplier,
        StateMachineFactoryInterface $stateMachineFactory
    ) {
        $this->container = $container;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->orderRepository = $orderRepository;
        $this->refundPaymentRepository = $refundPaymentRepository;
        $this->refundPaymentCompletedStateApplier = $refundPaymentCompletedStateApplier;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    /**
     * @param Request $request
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

        if ($lyraMarketplacePaymentMethod === null){
            return new Response();
        }

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
     * @param Request $request
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

        if ($lyraMarketplacePaymentMethod === null){
            return new Response();
        }

        $api = new Api();
        $api->setConfig($lyraMarketplacePaymentMethod->getGatewayConfig()->getConfig());
        $api->setContainer($this->container);

        $refund = $api->retrieveRefund($json['refund']);

        if (!($refund instanceof Refund)){
            return new Response();
        }

        $refundPayment = $this->refundPaymentRepository->findOneBy(['lyraRefundUuid' => $json['refund']]);
        if ($refundPayment !== null) {
            try {
                if ($refund->getStatus() === Refund::STATUS_SUCCEEDED &&
                    $this->stateMachineFactory->get($refundPayment, 'sylius_refund_refund_payment')->can('complete')
                ) {
                    $this->refundPaymentCompletedStateApplier->apply($refundPayment);
                }
            } catch (\Exception $e) {
            }

            return new Response();
        }

        /** @var ?Order $order  */
        $order = $this->orderRepository->findOneBy(['lyraOrderUuid' => $refund->getOrder()]);

        if (!($order instanceof Order)){
            return new Response();
        }

        if (in_array($refund->getStatus(), [Refund::STATUS_SUCCEEDED, Refund::STATUS_FAILED, Refund::STATUS_CANCELLED, Refund::STATUS_PENDING, Refund::STATUS_ABANDONED], true)) {
            /** @var Payment $payment */
            $payment = $order->getLastPayment();

            /** @var PaymentMethodInterface $paymentMethod */
            $paymentMethod = $payment->getMethod();

            /** @var GatewayConfigInterface $gatewayConfig */
            $gatewayConfig = $paymentMethod->getGatewayConfig();

            // Execute notify & status actions.
            $gateway = $this->getPayum()->getGateway($gatewayConfig->getGatewayName());

            $gateway->execute(new NotifyRefund($payment));
        }

        return new Response();
    }

    /**
     * @throws Exception
     */
    public function doTokenAction(Request $request): Response
    {
        $body = file_get_contents('php://input');
        $json = $this->fromJson($body);
        $token = $json['token'];

        $lyraMarketplacePaymentMethod = $this->paymentMethodRepository->findOneBy(['code' => 'lyra_market_place']);

        if ($lyraMarketplacePaymentMethod === null){
            return new Response();
        }

        $api = new Api();
        $api->setConfig($lyraMarketplacePaymentMethod->getGatewayConfig()->getConfig());
        $api->setContainer($this->container);

        $order = $this->orderRepository->findOneBy(['lyraOrderUuid' => $token]);

        if (!($order instanceof Order)){
            return new Response();
        }

        $tokenInfos = $api->retrieveToken($token);

        if ($tokenInfos instanceof OrderRegister){
            if (in_array($tokenInfos->getStatus(), [OrderSerializer::STATUS_CREATED, OrderSerializer::STATUS_SUCCEEDED, OrderSerializer::STATUS_FAILED, OrderSerializer::STATUS_CANCELLED, OrderSerializer::STATUS_PENDING, OrderSerializer::STATUS_ABANDONED], true)) {
                /** @var Payment $payment */
                $payment = $order->getLastPayment();

                /** @var PaymentMethodInterface $paymentMethod */
                $paymentMethod = $payment->getMethod();

                /** @var GatewayConfigInterface $gatewayConfig */
                $gatewayConfig = $paymentMethod->getGatewayConfig();

                // Execute notify & status actions.
                $gateway = $this->getPayum()->getGateway($gatewayConfig->getGatewayName());

                $gateway->execute(new NotifyToken($payment));
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
