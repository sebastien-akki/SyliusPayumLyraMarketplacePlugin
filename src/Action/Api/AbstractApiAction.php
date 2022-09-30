<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Action\Api;

use Akki\SyliusPayumLyraMarketplacePlugin\Api\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

/**
 * Class AbstractApiAction
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Action\Api
 */
abstract class AbstractApiAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @var Api|null
     */
    protected $api;

    /**
     * @inheritDoc
     */
    public function setApi($api): void
    {
        if (false === $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

}
