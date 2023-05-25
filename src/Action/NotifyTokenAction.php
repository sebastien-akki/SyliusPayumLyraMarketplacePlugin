<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\NotifyToken;
use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Sync;



class NotifyTokenAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param NotifyToken $request
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
        return $request instanceof NotifyToken
            && $request->getModel() instanceof ArrayAccess;
    }
}