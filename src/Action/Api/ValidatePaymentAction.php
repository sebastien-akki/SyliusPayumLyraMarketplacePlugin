<?php
declare(strict_types=1);

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Api\ValidatePayment;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\GetHumanStatus;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

final class ValidatePaymentAction extends AbstractApiAction
{
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $model->validateNotEmpty(['uuid']);

        $this->api->validatePayment($model['uuid']);

        $model['order'] = $this->api->retrieveOrder($model['uuid'])->__toString();

        $this->gateway->execute(new GetHumanStatus($model));
    }

    public function supports($request): bool
    {
        return $request instanceof ValidatePayment;
    }

}