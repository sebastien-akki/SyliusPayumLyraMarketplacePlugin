<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Entity\Refund;

use Sylius\RefundPlugin\Entity\RefundPaymentInterface as BaseRefundPaymentInterface;

interface RefundPaymentInterface extends BaseRefundPaymentInterface
{
    public function getLyraRefundUuid(): ?string;

    public function setLyraRefundUuid(?string $lyraRefundUuid): self;
}
