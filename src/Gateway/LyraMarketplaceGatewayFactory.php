<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Gateway;

use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\ApiRequestAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\ApiResponseAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\SyncOrderAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\SyncRefundAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\SyncTokenAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\ValidatePaymentAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\CancelAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\CaptureAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\ConvertPaymentAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\NotifyAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\NotifyRefundAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\NotifyTokenAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\RefundAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\StatusAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\SyncAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\TokenAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Api\Api;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\Api\ValidatePayment;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Payum\Core\GatewayFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LyraMarketplaceGatewayFactory
 * @package Akki\SyliusPayumLyraMarketplacePlugin
 */
class LyraMarketplaceGatewayFactory extends GatewayFactory
{
    private $container;

    /**
     * @param array $defaultConfig
     * @param GatewayFactoryInterface|null $coreGatewayFactory
     * @param ContainerInterface $container
     */
    public function __construct(
        array $defaultConfig = array(),
        GatewayFactoryInterface $coreGatewayFactory = null,
        ContainerInterface $container

    )
    {
        parent::__construct($defaultConfig,$coreGatewayFactory);
        $this->container = $container;
    }

    /**
     * Builds a new factory.
     *
     * @param array $defaultConfig
     * @param GatewayFactoryInterface|null $coreGatewayFactory
     * @param ContainerInterface $container
     * @return LyraMarketplaceGatewayFactory
     */
    public static function build(array $defaultConfig, GatewayFactoryInterface $coreGatewayFactory = null, ContainerInterface $container): LyraMarketplaceGatewayFactory
    {
        return new static($defaultConfig, $coreGatewayFactory, $container);
    }

    /**
     * @inheritDoc
     */
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name'  => 'lyra_marketplace',
            'payum.factory_title' => 'lyra_marketplace',

            'payum.action.capture'         => new CaptureAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.cancel'          => new CancelAction(),
            'payum.action.refund'          => new RefundAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.notify_refund'   => new NotifyRefundAction(),
            'payum.action.api.request'     => new ApiRequestAction(),
            'payum.action.api.response'    => new ApiResponseAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.api.sync_order'  => new SyncOrderAction(),
            'payum.action.api.sync_refund'  => new SyncRefundAction(),
            'payum.action.sync'            => new SyncAction(),
            'payum.action.notify_token'    => new NotifyTokenAction(),
            'payum.action.api.sync_token'  => new SyncTokenAction(),
            'payum.action.api.validate_payment' => new ValidatePaymentAction()
        ]);

        if (!$config['payum.api']) {
            $config['payum.default_options'] = [
                'username'     => null,
                'password' => null,
                'ctx_mode'    => null,
                'marketplace_uuid'    => null,
                'marketplace_public_key'    => null,
            ];

            $config->defaults($config['payum.default_options']);

            $config['payum.required_options'] = ['username', 'password', 'ctx_mode', 'marketplace_uuid', 'marketplace_public_key'];

            $container = $this->container;

            $config['payum.api'] = static function (ArrayObject $config) use ($container){
                $config->validateNotEmpty($config['payum.required_options']);

                $lyraMarketplaceConfig = [
                    'username'     => $config['username'],
                    'password' => $config['password'],
                    'ctx_mode'    => $config['ctx_mode'],
                    'marketplace_uuid'    => $config['marketplace_uuid'],
                    'marketplace_public_key'    => $config['marketplace_public_key'],
                ];

                $api = new Api();
                $api->setConfig($lyraMarketplaceConfig);
                $api->setContainer($container);

                return $api;
            };
        }
    }
}
