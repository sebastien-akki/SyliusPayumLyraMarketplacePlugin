<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Api\SyncOrder;
use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Swagger\Client\ApiException;

class SyncOrderAction extends AbstractApiAction
{
    /**
     * {@inheritDoc}
     *
     * @param SyncOrder $request
     *
     * @throws ApiException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['order','uuid']);

        $orderSerializer = $this->api->retrieveOrder($model['uuid']);
        if ($orderSerializer !== null){
            $model['order'] =  $orderSerializer->__toString();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof SyncOrder &&
            $request->getModel() instanceof ArrayAccess
            ;
    }
}