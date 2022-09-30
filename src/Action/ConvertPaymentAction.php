<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Api\Api;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\PaymentInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Convert;
use Payum\Core\Request\GetCurrency;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\Product;

/**
 * Class ConvertPaymentAction
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Action
 */
class ConvertPaymentAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $model = ArrayObject::ensureArrayObject($payment->getDetails());

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $model['order_id'] = $order->getNumber();

        $request->setResult((array)$model);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return $request instanceof Convert
            && $request->getSource() instanceof PaymentInterface
            && $request->getTo() === 'array';
    }

}
