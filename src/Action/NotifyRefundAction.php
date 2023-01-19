<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\NotifyRefund;
use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Sync;

/**
 * Class NotifyRefundAction
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Action
 */
class NotifyRefundAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param NotifyRefund $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute(new Sync($details));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return $request instanceof NotifyRefund
            && $request->getModel() instanceof ArrayAccess;
    }
}
