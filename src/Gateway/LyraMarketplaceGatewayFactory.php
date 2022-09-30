<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Gateway;

use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\ApiRequestAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\Api\ApiResponseAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\CancelAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\CaptureAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\ConvertPaymentAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\NotifyAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\RefundAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\StatusAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Action\SyncAction;
use Akki\SyliusPayumLyraMarketplacePlugin\Api\Api;
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
            'payum.action.sync'            => new SyncAction(),
            'payum.action.cancel'          => new CancelAction(),
            'payum.action.refund'          => new RefundAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.api.request'     => new ApiRequestAction(),
            'payum.action.api.response'    => new ApiResponseAction(),
        ]);

        if (!$config['payum.api']) {
            $config['payum.default_options'] = [
                'username'     => null,
                'password' => null,
                'ctx_mode'    => null,
            ];

            $config->defaults($config['payum.default_options']);

            $config['payum.required_options'] = ['username', 'password', 'ctx_mode',];

            $container = $this->container;

            $config['payum.api'] = static function (ArrayObject $config) use ($container){
                $config->validateNotEmpty($config['payum.required_options']);

                $lyraMarketplaceConfig = [
                    'username'     => $config['username'],
                    'password' => $config['password'],
                    'ctx_mode'    => $config['ctx_mode'],
                ];

                $api = new Api();
                $api->setConfig($lyraMarketplaceConfig);
                $api->setContainer($container);

                return $api;
            };
        }
    }
}
