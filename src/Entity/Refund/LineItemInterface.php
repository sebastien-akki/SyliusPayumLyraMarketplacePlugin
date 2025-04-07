<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Entity\Refund;

use Sylius\Component\Core\Model\Product;
use Sylius\RefundPlugin\Entity\LineItemInterface as BaseLineItemInterface;

interface LineItemInterface extends BaseLineItemInterface
{
    public function setProduct(?Product $product): self;

    public function getProduct(): ?Product;
}
