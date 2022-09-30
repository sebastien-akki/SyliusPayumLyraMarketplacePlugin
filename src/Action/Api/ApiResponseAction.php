<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Response;
use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHttpRequest;

/**
 * Class ResponseAction
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Action\Api
 */
class ApiResponseAction extends AbstractApiAction
{
    /**
     * @inheritdoc
     */
    public function execute($request): void
    {
        /** @var Response $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (!empty($httpRequest->request)) {
            $data = $httpRequest->request;
        } elseif (!empty($httpRequest->query)) {
            $data = $httpRequest->query;
        } else {
            return;
        }

        $model->replace($data);
        $request->setModel($model);
    }

    /**
     * @inheritdec
     * @param $request
     * @return bool
     */
    public function supports($request): bool
    {
        return $request instanceof Response
            && $request->getModel() instanceof ArrayAccess;
    }
}
