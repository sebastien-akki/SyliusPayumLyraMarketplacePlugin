<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\AbstractApiAction;
use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Refund;

/**
 * Class CaptureAction
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Action
 */
class RefundAction extends AbstractApiAction
{

    /**
     * {@inheritdoc}
     *
     * @param Refund $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $orderSerializer = $this->api->sendRefund($model['uuid']);

        if ($orderSerializer !== null){
            $model['refund'] =  $orderSerializer->__toString();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return $request instanceof Refund
            && $request->getModel() instanceof ArrayAccess;
    }
}
