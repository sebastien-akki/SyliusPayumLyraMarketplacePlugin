<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Api\SyncOrder;
use ArrayAccess;
use JsonException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetStatusInterface;
use Swagger\Client\Model\OrderSerializer;
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

        if($model['order']) {
            $this->gateway->execute(new SyncOrder($model));
            $order = ObjectSerializer::deserialize(json_decode($model['order'], false, 512, JSON_THROW_ON_ERROR), OrderSerializer::class, []);
            $code = $order->getStatus();
            switch ($code) {
                case OrderSerializer::STATUS_PENDING :
                case OrderSerializer::STATUS_SUCCEEDED : // transaction approuvée ou traitée avec succès
                    $request->markCaptured();
                    break;
                case OrderSerializer::STATUS_CREATED :
                case OrderSerializer::STATUS_CANCELLED :
                    $request->markCanceled();
                    break;
                case OrderSerializer::STATUS_FAILED :
                    $request->markNew();
                    break;
                case OrderSerializer::STATUS_ABANDONED :
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
