<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Request;
use ArrayAccess;
use Exception;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;

/**
 * Class RequestAction
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Action\Api
 */
class ApiRequestAction extends AbstractApiAction
{
    /**
     * @inheritdoc
     *
     * @throws HttpRedirect
     * @throws Exception
     */
    public function execute($request): void
    {
        /** @var Request $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model['url_marketplace_known'] = true;

        if (!($model['order'])) {
            $result = $this->api->retrieveMarketPlaceUrl($model['order_id'],$model['url_success']);
            $model['url_marketplace'] = $result['url'];
            $model['uuid'] = $result['uuid'];
            $orderSerializer = $this->api->retrieveOrder($model['uuid']);
            if ($orderSerializer !== null) {
                $model['order'] = $orderSerializer->__toString();
            }
        }


        throw new HttpRedirect($model['url_marketplace']);
    }

    /**
     * @inheritdoc
     */
    public function supports($request): bool
    {
        return $request instanceof Request
            && $request->getModel() instanceof ArrayAccess;
    }
}
