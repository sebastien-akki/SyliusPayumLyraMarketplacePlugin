<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Api\SyncOrder;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\GetHumanRefundStatus;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\SyncRefund;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\SyncToken;
use ArrayAccess;
use JsonException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetStatusInterface;
use Swagger\Client\Model\OrderRegister;
use Swagger\Client\Model\OrderSerializer;
use Swagger\Client\Model\Refund;
use Swagger\Client\ObjectSerializer;

/**
 * Class StatusAction
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Action
 */
class StatusAction implements ActionInterface, GatewayAwareInterface
{

    use GatewayAwareTrait;

    /**
     * {@inheritdoc}
     *
     * @param GetStatusInterface $request
     * @throws JsonException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if($model['refund']) {

            if ($model['refund'] === 'REFUSED'){
                $request->markFailed();
                return;
            }

            $this->gateway->execute(new SyncRefund($model));
            $refund = ObjectSerializer::deserialize(json_decode($model['refund'], false, 512, JSON_THROW_ON_ERROR), Refund::class, []);
            $code = $refund->getStatus();
            switch ($code) {
                case Refund::STATUS_SUCCEEDED : // transaction approuvée ou traitée avec succès
                    $request->markRefunded();
                    break;
                case Refund::STATUS_CREATED :
                case Refund::STATUS_PENDING :
                    $request->markPending();
                    break;
                case Refund::STATUS_CANCELLED :
                    $request->markCanceled();
                    break;
                case Refund::STATUS_FAILED :
                case Refund::STATUS_ABANDONED :
                    $request->markFailed();
                    break;
                default :
                    $request->markUnknown();
            }

            return;
        }

        if($model['order']) {
            $this->gateway->execute(new SyncOrder($model));
            $this->gateway->execute(new SyncToken($model));
            $order = ObjectSerializer::deserialize(json_decode($model['order'], false, 512, JSON_THROW_ON_ERROR), OrderSerializer::class, []);
            $token = ObjectSerializer::deserialize(json_decode($model['token'], false, 512, JSON_THROW_ON_ERROR), OrderRegister::class, []);
            $orderStatus = $order->getStatus();
            $tokenStatus = $token->getStatus();

            switch (true) {
                case $orderStatus === OrderSerializer::STATUS_PENDING || $orderStatus === OrderSerializer::STATUS_SUCCEEDED || $tokenStatus === OrderSerializer::STATUS_PENDING || $tokenStatus === OrderSerializer::STATUS_SUCCEEDED:
                    $request->markCaptured();
                    break;
                case $orderStatus === OrderSerializer::STATUS_CREATED || $orderStatus === OrderSerializer::STATUS_CANCELLED :
                    $request->markCanceled();
                    break;
                case $orderStatus === OrderSerializer::STATUS_FAILED :
                    $request->markNew();
                    break;
                case $orderStatus === OrderSerializer::STATUS_ABANDONED :
                    $request->markFailed();
                    break;
                default :
                    $request->markUnknown();
            }

            $code = $model['status'];
            if ($code === 'refunded' && $request->isCaptured()) {
                $request->markRefunded();
            }
            return;
        }

        if (!$model['url_marketplace_known']) {
            $request->markNew();
            return;
        }

//        if (array_key_exists('vads_result',$_REQUEST)) {
//            $data = array_merge($model->getArrayCopy(), $_REQUEST);
//            $model->replace($data);
//        }

        $code = $model['status'];
        if ($code === 'canceled') {
            $request->markCanceled();
            return;
        }

        $request->markNew();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return $request instanceof GetStatusInterface
            && $request->getModel() instanceof ArrayAccess;
    }
}
