<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Api\SyncOrder;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\SyncRefund;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\SyncToken;
use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Sync;

/**
 * Class SyncAction
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Action
 */
class SyncAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritdoc}
     *
     * @param Sync $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if($model['refund']) {
            $this->gateway->execute(new SyncRefund($model));
        }elseif ($model['token']) {
            $this->gateway->execute(new SyncToken($model));
        }
        else if($model['order']) {
            $this->gateway->execute(new SyncOrder($model));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return $request instanceof Sync
            && $request->getModel() instanceof ArrayAccess;
    }
}
