<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Service\LyraMarketplaceService;
use Doctrine\Persistence\ObjectManager;
use Payum\Core\Exception\LogicException;
use Swagger\Client\ApiException;
use Swagger\Client\Configuration;
use Swagger\Client\Model\GetTokenDetails;
use Swagger\Client\Model\OrderRegister;
use Swagger\Client\Model\OrderSerializer;
use Swagger\Client\Model\Refund;
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
     * @return array
     */
    public function retrieveMarketPlaceUrl(string $orderId, String $returnUrl): array
    {
        /** @var ObjectManager  $entityManager */
        $entityManager = $this->container->get('sylius.manager.order');

        /** @var RepositoryInterface $entityRepository */
        $entityRepository = $this->container->get('sylius.repository.order');

        $order = $entityRepository !== null ? $entityRepository->find((int)$orderId) : null;

        if ($order instanceof Order && !empty($order->getLyraMarketplacePaymentUrl())){

            return array(
                'url' => $order->getLyraMarketplacePaymentUrl(),
                'uuid' => $order->getLyraOrderUuid(),
                'order' => $order,
            );
        }

        if ($order instanceof Order){
            $marketplaceService = new LyraMarketplaceService($entityManager,$this->createConfigurationMarketplace(),$this->getMarketplaceUUID());
            $marketplaceService->generate($order,$returnUrl);

            return array(
                'url' => $order->getLyraMarketplacePaymentUrl(),
                'uuid' => $order->getLyraOrderUuid(),
                'order' => $order,
            );
        }

        return array(
            'url' => "",
            'uuid' => "",
            'order' => "",
        );
    }

    /**
     * @param string|null $uuid
     *
     * @return OrderSerializer|null
     *
     * @throws ApiException
     */
    public function retrieveOrder(?string $uuid): ?OrderSerializer
    {
        /** @var ObjectManager $entityManager */
        $entityManager = $this->container->get('sylius.manager.order');

        if ($uuid !== null){
            $marketplaceService = new LyraMarketplaceService($entityManager,$this->createConfigurationMarketplace(),$this->getMarketplaceUUID());
            return $marketplaceService->readOrder($uuid);
        }

        return null;
    }

    /**
     * @param string $orderId
     *
     * @return Refund|null
     */
    public function sendRefund(string $orderId): ?Refund
    {
        /** @var ObjectManager  $entityManager */
        $entityManager = $this->container->get('sylius.manager.order');

        /** @var RepositoryInterface $entityRepository */
        $entityRepository = $this->container->get('sylius.repository.order');

        $order = $entityRepository !== null ? $entityRepository->find((int)$orderId) : null;

        if ($order instanceof Order && !empty($order->getLyraOrderUuid())){

            $marketplaceService = new LyraMarketplaceService($entityManager,$this->createConfigurationMarketplace(),$this->getMarketplaceUUID());
            return $marketplaceService->refundOrder($order);
        }

        return null;
    }

    /**
     * @param string|null $uuid
     *
     * @return OrderSerializer|null
     *
     * @throws ApiException
     */
    public function retrieveRefund(?string $uuid): ?Refund
    {
        /** @var ObjectManager $entityManager */
        $entityManager = $this->container->get('sylius.manager.order');

        if ($uuid !== null){
            $marketplaceService = new LyraMarketplaceService($entityManager,$this->createConfigurationMarketplace(),$this->getMarketplaceUUID());
            return $marketplaceService->readRefund($uuid);
        }

        return null;
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
    public function getMarketplaceUUID(): string
    {
        $this->ensureApiIsConfigured();
        return (string)$this->config['marketplace_uuid'];
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
                'marketplace_uuid',
            ])
            ->setAllowedTypes('username', 'string')
            ->setAllowedTypes('password', 'string')
            ->setAllowedTypes('marketplace_uuid', 'string')
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

    /**
     * @throws ApiException
     */
    public function retrieveToken($token): ?OrderRegister
    {
        $entityManager = $this->container->get('sylius.manager.order');
        if ($token === null){
            return null;
        }
        $marketplaceService = new LyraMarketplaceService($entityManager,$this->createConfigurationMarketplace(),$this->getMarketplaceUUID());
        return $marketplaceService->retrieveToken($token);

    }

    /**
     * @throws ApiException
     */
    public function retrieveAlias($alias): ?GetTokenDetails
    {
        $entityManager = $this->container->get('sylius.manager.order');
        if ($alias === null){
            return null;
        }
        $marketplaceService = new LyraMarketplaceService($entityManager,$this->createConfigurationMarketplace(),$this->getMarketplaceUUID());

        return $marketplaceService->retrieveAlias($alias);
    }

}
