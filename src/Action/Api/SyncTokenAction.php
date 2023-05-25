<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action\Api;


use Akki\SyliusPayumLyraMarketplacePlugin\Request\SyncToken;
use ArrayAccess;
use Swagger\Client\ApiException;

class SyncTokenAction extends AbstractApiAction
{
    /**
     * @throws ApiException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['token','uuid']);

        $token = $this->api->retrieveToken($model['uuid']);
        if ($token !== null){
            $model['token'] =  $token->__toString();

            $alias = $this->api->retrieveAlias($token->getAlias());
            if ($alias !== null){
                $model['alias'] =  $alias->__toString();
            }
        }

    }

    /**
     * @param $request
     * @return bool
     */
    public function supports($request): bool
    {
        return
            $request instanceof SyncToken &&
            $request->getModel() instanceof ArrayAccess
            ;
    }


}