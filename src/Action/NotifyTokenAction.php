<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\NotifyToken;

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