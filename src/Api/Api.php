<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Service\LyraMarketplaceService;
use Doctrine\Persistence\ObjectManager;
use Payum\Core\Exception\LogicException;
use Swagger\Client\Configuration;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Api
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Api
 */
class Api
{
    public const MODE_TEST       = 'TEST';
    public const MODE_PRODUCTION = 'PRODUCTION';

    /** @var OptionsResolver|null $configResolver */
    private $configResolver;

    /** @var ContainerInterface $container */
    private $container;

    /** @var array */
    private $config;

    /**
     * @param $ctxMode
     *
     * @return string
     */
    public static function getUrlFromEndpoint($ctxMode): string
    {
        if ($ctxMode === self::MODE_TEST){
            return 'https://secure.lyra.com/marketplace-test';
        }

        return 'https://secure.lyra.com/marketplace';
    }

    /**
     * @param string $orderId
     * @param String $returnUrl
     *
     * @return string
     */
    public function retrieveMarketPlaceUrl(string $orderId, String $returnUrl): string
    {
        /** @var ObjectManager  $entityManager */
        $entityManager = $this->container->get('sylius.manager.order');

        /** @var RepositoryInterface $entityRepository */
        $entityRepository = $this->container->get('sylius.repository.order');

        $order = $entityRepository !== null ? $entityRepository->find((int)$orderId) : null;

        if ($order instanceof Order && !empty($order->getLyraMarketplacePaymentUrl())){
            return $order->getLyraMarketplacePaymentUrl();
        }

        if ($order instanceof Order){
            $marketplaceService = new LyraMarketplaceService($entityManager,$this->createConfigurationMarketplace());
            $marketplaceService->generate($order,$returnUrl);
            return $order->getLyraMarketplacePaymentUrl();
        }

        return "";
    }

    private function createConfigurationMarketplace(): Configuration
    {
        return Configuration::getDefaultConfiguration()
            ->setUsername($this->getMarketplaceUsername())
            ->setPassword($this->getMarketplacePassword())
            ->setHost($this->createRequestUrl());
    }


    /**
     * Configures the api.
     *
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $this
            ->getConfigResolver()
            ->resolve($config);
    }

    /**
     * Configures the container.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Creates the request url.
     *
     * @return string
     */
    public function createRequestUrl(): string
    {
        $this->ensureApiIsConfigured();
        return $this->getUrl();
    }

    /**
     * retrieve marketplace username.
     *
     * @return string
     */
    public function getMarketplaceUsername(): string
    {
        $this->ensureApiIsConfigured();
        return (string)$this->config['username'];
    }

    /**
     * retrieve marketplace username.
     *
     * @return string
     */
    public function getMarketplacePassword(): string
    {
        $this->ensureApiIsConfigured();
        return (string)$this->config['password'];
    }

    /**
     * Check that the API has been configured.
     *
     * @throws LogicException
     */
    private function ensureApiIsConfigured(): void
    {
        if (null === $this->config) {
            throw new LogicException('You must first configure the API.');
        }
    }

    /**
     * Returns the config option resolver.
     *
     * @return OptionsResolver
     */
    private function getConfigResolver(): OptionsResolver
    {
        if (null !== $this->configResolver) {
            return $this->configResolver;
        }

        $resolver = new OptionsResolver();
        $resolver
            ->setRequired([
                'username',
                'password',
                'ctx_mode',
            ])
            ->setAllowedTypes('username', 'string')
            ->setAllowedTypes('password', 'string')
            ->setAllowedValues('ctx_mode', $this->getModes())
            ;

        return $this->configResolver = $resolver;
    }

    private function getModes(): array
    {
        return [self::MODE_TEST, self::MODE_PRODUCTION];
    }

    private function getUrl(): string
    {
        return self::getUrlFromEndpoint((string)$this->config['ctx_mode']);
    }

}
