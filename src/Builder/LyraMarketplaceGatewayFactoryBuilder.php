<?php
namespace Akki\SyliusPayumLyraMarketplacePlugin\Builder;

use Payum\Core\GatewayFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LyraMarketplaceGatewayFactoryBuilder
{
    /**
     * @var string
     */
    private $gatewayFactoryClass;

    private $container;

    /**
     * @param string $gatewayFactoryClass
     * @param ContainerInterface $container
     */
    public function __construct(
        string $gatewayFactoryClass,
        ContainerInterface $container
    )
    {
        $this->gatewayFactoryClass = $gatewayFactoryClass;
        $this->container = $container;
    }

    /**
     * @param array $defaultConfig
     * @param GatewayFactoryInterface $coreGatewayFactory
     *
     * @return GatewayFactoryInterface
     */
    public function build(array $defaultConfig, GatewayFactoryInterface $coreGatewayFactory)
    {
        $gatewayFactoryClass = $this->gatewayFactoryClass;
        $container = $this->container;

        return new $gatewayFactoryClass($defaultConfig, $coreGatewayFactory, $container);
    }

    public function __invoke()
    {
        return call_user_func_array([$this, 'build'], func_get_args());
    }
}
