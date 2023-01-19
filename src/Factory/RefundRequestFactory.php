<?php

declare(strict_types=1);

namespace Akki\SyliusPayumLyraMarketplacePlugin\Factory;

use Payum\Core\Model\ModelAggregateInterface;
use Payum\Core\Request\Refund;
use Payum\Core\Security\TokenInterface;

final class RefundRequestFactory implements RefundRequestFactoryInterface
{
    public function createNewWithToken(TokenInterface $token): ModelAggregateInterface
    {
        return new Refund($token);
    }
}