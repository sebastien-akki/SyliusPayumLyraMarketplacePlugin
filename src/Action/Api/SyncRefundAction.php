<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\SyncRefund;
use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Swagger\Client\ApiException;

class SyncRefundAction extends AbstractApiAction
{
    /**
     * {@inheritDoc}
     *
     * @param SyncRefund $request
     *
     * @throws ApiException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['refund','refund_uuid']);

        $refund = $this->api->retrieveRefund($model['refund_uuid']);
        if ($refund !== null){
            $model['$refund'] =  $refund->__toString();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof SyncRefund &&
            $request->getModel() instanceof ArrayAccess
            ;
    }
}