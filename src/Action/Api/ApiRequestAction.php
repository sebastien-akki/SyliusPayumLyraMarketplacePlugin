<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Request;
use ArrayAccess;
use DateTime;
use DateTimeZone;
use Exception;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use function array_key_exists;

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

        if ($model['url_marketplace']) {
            return;
        }
        $model['url_marketplace'] = true;

        $url = $this->api->retrieveMarketPlaceUrl($model['order_id'],$model['url_success']);

        throw new HttpRedirect($url);
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
