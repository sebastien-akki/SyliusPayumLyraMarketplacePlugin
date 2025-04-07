<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Converter\LineItem;

use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\OrderItemUnitInterface;
use Sylius\Component\Resource\Metadata\RegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\RefundPlugin\Converter\LineItem\LineItemsConverterUnitRefundAwareInterface;
use Sylius\RefundPlugin\Entity\LineItemInterface;
use Sylius\RefundPlugin\Model\OrderItemUnitRefund;
use Sylius\RefundPlugin\Provider\TaxRateProviderInterface;
use Webmozart\Assert\Assert;

class OrderItemUnitLineItemsConverter implements LineItemsConverterUnitRefundAwareInterface
{
    public function __construct(
        private RepositoryInterface $orderItemUnitRepository,
        private TaxRateProviderInterface $taxRateProvider,
        private RegistryInterface $registry
    ) {
    }

    public function convert(array $units): array
    {
        Assert::allIsInstanceOf($units, $this->getUnitRefundClass());

        $lineItems = [];

        /** @var OrderItemUnitRefund $unit */
        foreach ($units as $unit) {
            $lineItems = $this->addLineItem($this->convertUnitRefundToLineItem($unit), $lineItems);
        }

        return $lineItems;
    }

    public function getUnitRefundClass(): string
    {
        return OrderItemUnitRefund::class;
    }

    private function convertUnitRefundToLineItem(OrderItemUnitRefund $unitRefund): LineItemInterface
    {
        /** @var OrderItemUnitInterface|null $orderItemUnit */
        $orderItemUnit = $this->orderItemUnitRepository->find($unitRefund->id());
        Assert::notNull($orderItemUnit);
        Assert::lessThanEq($unitRefund->total(), $orderItemUnit->getTotal());

        /** @var OrderItemInterface $orderItem */
        $orderItem = $orderItemUnit->getOrderItem();

        $grossValue = $unitRefund->total();
        $taxAmount = (int) ($grossValue * $orderItemUnit->getTaxTotal() / $orderItemUnit->getTotal());
        $netValue = $grossValue - $taxAmount;

        /** @var string|null $productName */
        $productName = $orderItem->getProductName();
        Assert::notNull($productName);

        $metadata = $this->registry->get('sylius_refund.line_item');
        $className = $metadata->getClass('model');

        /** @var \Akki\SyliusPayumLyraMarketplacePlugin\Entity\Refund\LineItemInterface $lineItem */
        $lineItem = new $className(
            $productName,
            1,
            $netValue,
            $grossValue,
            $netValue,
            $grossValue,
            $taxAmount,
            $this->taxRateProvider->provide($orderItemUnit),
        );

        $lineItem->setProduct($orderItem->getProduct());

        return $lineItem;
    }

    /**
     * @param LineItemInterface[] $lineItems
     *
     * @return LineItemInterface[]
     */
    private function addLineItem(LineItemInterface $newLineItem, array $lineItems): array
    {
        foreach ($lineItems as $lineItem) {
            if ($lineItem->compare($newLineItem)) {
                $lineItem->merge($newLineItem);

                return $lineItems;
            }
        }

        $lineItems[] = $newLineItem;

        return $lineItems;
    }
}

class_alias(OrderItemUnitLineItemsConverter::class, \Sylius\RefundPlugin\Converter\OrderItemUnitLineItemsConverter::class);
