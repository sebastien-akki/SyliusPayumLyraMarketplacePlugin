<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\AbstractApiAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\GetHumanRefundStatus;
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

        $refund = $this->api->sendRefund($model['order_id']);

        if ($refund !== null){
            $model['refund'] =  $refund->__toString();
            $model['refund_uuid'] = $refund->getUuid();
        } else {
            $model['refund'] = 'REFUSED';
            $model['refund_uuid'] = 'REFUSED';
        }

        $this->gateway->execute($status = new GetHumanRefundStatus($model));
//        $this->gateway->execute(new Sync($model));
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
