<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use Payum\Core\Model\GatewayConfigInterface;
use Swagger\Client\Api\ItemsApi;
use Swagger\Client\Api\MarketplacesApi;
use Swagger\Client\Api\OrdersApi;
use Swagger\Client\Api\RefundsApi;
use Swagger\Client\Api\SellersApi;
use Swagger\Client\Api\TokensApi;
use Swagger\Client\ApiException;
use Swagger\Client\Configuration;
use Swagger\Client\Model\BuyerSerializerLegacy;
use Swagger\Client\Model\GetTokenDetails;
use Swagger\Client\Model\ItemSerializer;
use Swagger\Client\Model\OrderRegister;
use Swagger\Client\Model\OrderSerializer;
use Swagger\Client\Model\Refund;
use Swagger\Client\Model\RefundItem;
use Swagger\Client\Model\ShippingSerializerLegacy;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Customer\Model\CustomerInterface;
use function getenv;

class LyraMarketplaceService
{
    private $ordersApi ;

    private $refundsApi ;

    private $marketplaceApi ;

    private $itemsApi ;

    private $expand ='items';

    private $items = array() ;

    /** @var OrderSerializer **/
    private $orderSerializer ;

    private $entityManagerInterface ;

    private $sellersApi ;

    private $marketplaceUUID = null;

    private $tokenApi ;

    public function __construct(EntityManagerInterface $entityManager, Configuration $configuration, string $marketplaceUUID)
    {
        $this->entityManagerInterface = $entityManager ;
        $this->marketplaceUUID = $marketplaceUUID ;
        $this->orderSerializer = new OrderSerializer() ;
        $this->ordersApi = new OrdersApi(new Client(), $configuration) ;
        $this->refundsApi = new RefundsApi(new Client(), $configuration) ;
        $this->marketplaceApi = new MarketplacesApi(new Client(), $configuration) ;
        $this->itemsApi = new ItemsApi(new Client(), $configuration) ;
        $this->sellersApi = new SellersApi(new Client(), $configuration) ;
        $this->tokenApi = new TokensApi(new Client(), $configuration) ;
    }

    /**
     * @param Order $order
     * @param String $returnUrl
     * @return mixed
     */
    public function generate(Order $order, String $returnUrl)
    {
        // si on est sur un paiement de type marketplace
        $lastPayment = $order->getLastPayment();

        if (!($lastPayment instanceof PaymentInterface)){
            return null;
        }

        /** @var PaymentMethodInterface|null $paymentMethod */
        $paymentMethod = $lastPayment->getMethod();

        if (!($paymentMethod instanceof PaymentMethodInterface)){
            return null;
        }

        /** @var GatewayConfigInterface|null $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();

        if (!($gatewayConfig instanceof GatewayConfigInterface)){
            return null;
        }

        $responseCreateOrder = $this->createOrder($order, $returnUrl);
        if ($responseCreateOrder) {
            $uuid = $responseCreateOrder->getUuid();
            $responseExecuteOrder = $this->executeOrder($uuid);
            if ($responseExecuteOrder) {
                $order->setLyraMarketplacePaymentUrl($responseExecuteOrder['payment_url']);
                $this->entityManagerInterface->flush();
                return $responseExecuteOrder;
            }
        }

        return null;
    }

    /**
     * @param Order $order
     * @return mixed
     */
    public function getFormToken(Order $order)
    {
        $this->ordersApi->ordersDelete($order->getLyraOrderUuid());
        $responseCreateOrder = $this->createOrder($order, null);
        if ($responseCreateOrder) {
            $uuid = $responseCreateOrder->getUuid();
            $responseExecuteOrder = $this->getFormTokenEmbedded($uuid);
            if ($responseExecuteOrder) {
                return $responseExecuteOrder;
            }
        }

        return null;
    }

    /**
     * @param string $uuid
     *
     * @return OrderSerializer|null
     *
     * @throws ApiException
     */
    public function readOrder(string $uuid): ?OrderSerializer
    {

        return $this->ordersApi->ordersRead($uuid);
    }

    /**
     * @param string $uuid
     *
     * @return OrderSerializer|null
     *
     * @throws ApiException
     */
    public function readRefund(string $uuid): ?Refund
    {

        return $this->refundsApi->refundsRead($uuid);
    }

    /**
     * @param Order $order
     * @param String|null $returnUrl
     * @param bool $forceNew
     * @return OrderSerializer|void
     */
    public function createOrder(Order $order, ?String $returnUrl, bool $forceNew = false)
    {
        $this->processOrder($order, $returnUrl, false, $forceNew);
        try {
            $response = $this->ordersApi->ordersCreate($this->orderSerializer, $this->expand);
            $order->setLyraOrderUuid($response->getUuid());
            $this->entityManagerInterface->flush();
            return $response ;
        } catch (Exception $e) {
            echo 'Exception when calling OrdersApi->ordersCreate: ', $e->getMessage(), PHP_EOL;
        }
    }


    /**
     * @param Order $order
     * @param String $returnUrl
     * @return OrderSerializer|void
     */
    public function updateOrder(Order $order, String $returnUrl)
    {
        $this->processOrder( $order, $returnUrl, true );
        try {
            return $this->ordersApi->ordersUpdate($this->orderSerializer, $order->getLyraOrderUuid(), $this->expand) ;
        } catch (ApiException $e) {
            echo 'Exception when calling OrdersApi->ordersUpdate: ', $e->getMessage(), PHP_EOL;

        }
    }

    /**
     * @param $uuid
     * @return object|void
     */
    public function executeOrder($uuid)
    {
        try {
            return $this->ordersApi->ordersExecuteExecuteToken($uuid) ;
        }catch (Exception $e){
            echo 'Exception when calling OrdersApi->ordersExecuteExecuteToken: ', $e->getMessage(), PHP_EOL;

        }
    }

    /**
     * @param $uuid
     * @return object|void
     */
    public function getFormTokenEmbedded($uuid)
    {
        try {
            return $this->ordersApi->ordersExecuteEmbeddedExecuteEmbedded($uuid) ;
        }catch (Exception $e){
            echo 'Exception when calling OrdersApi->ordersExecuteEmbeddedExecuteEmbedded: ', $e->getMessage(), PHP_EOL;

        }
    }

    /**
     * @param Order $order
     *
     * @return Refund
     */
    public function refundOrder(Order $order)
    {
        $refund = $this->processOrderRefund($order);
        try {
            return $this->refundsApi->refundsCreate($refund);
        } catch (Exception $e) {
            echo 'Exception when calling refundsApi->refundsCreate: ', $e->getMessage(), PHP_EOL;
        }
    }

    /**
     * @return object|void
     */
    public function getMarketplaceList(){

        try {
            return  $this->marketplaceApi->marketplacesList();
        } catch (Exception $e) {
            echo 'Exception when calling MarketplacesApi->marketplacesList: ', $e->getMessage(), PHP_EOL;
        }
    }

    /**
     * @return object|void
     */
    public function getSellers()
    {
        try {
            return  $this->sellersApi->sellersList();
        } catch (Exception $e) {
            echo 'Exception when calling sellersApi->sellersList() : ', $e->getMessage(), PHP_EOL;
        }
    }

    /**
     * @param $uuid
     * @return OrderSerializer|void
     */
    private function getOrder($uuid)
    {
        try {
            return $this->ordersApi->ordersRead($uuid) ;
        } catch (Exception $e) {
            echo 'Exception when calling OrdersApi->ordersRead: ', $e->getMessage(), PHP_EOL;
        }
    }

    /**
     * @param Order $order
     * @param String|null $returnUrl
     * @param bool $update
     * @param bool $forceNew
     * @return void
     */
    private function processOrder(Order $order, ?String $returnUrl, bool $update =  false, bool $forceNew = false): void
    {
        $this->hydrateItemsFromOrder($order, $update);
        $lyraOrder = null;
        if (!$forceNew) {
            $lyraOrder = $this->getOrder($order->getLyraOrderUuid()) ;
        }
        if ($lyraOrder instanceof OrderSerializer){
            $data = $lyraOrder ;
        }else{
            $data = new OrderSerializer() ;
        }
        $data->setItems($this->items) ;
        if (!$update){

            $data->setMarketplace($this->marketplaceUUID) ;
            $data->setReference($order->getId());
            $data->setDescription($order->getId(). "-" . $order->getNumber());

            /** @var Customer $customer */
            $customer = $order->getCustomer();

            /** @var Address $billingAddress */
            $billingAddress = $order->getBillingAddress();

            $buyer = new BuyerSerializerLegacy() ;
            $buyer->setReference($customer->getId()) ;

            if (!empty($billingAddress->getCompany())){
                $buyer->setLegalName($billingAddress->getCompany()) ;
            }
            $buyer->setTitle($this->getCivilite($customer->getGender())) ;
            !empty($billingAddress->getCompany()) ? $buyer->setType('COMPANY') : $buyer->setType('PRIVATE');
            $buyer->setFirstName($customer->getFirstName()) ;
            $buyer->setLegalName($customer->getLastName()) ;
            $buyer->setPhoneNumber($customer->getPhoneNumber()) ;
            $buyer->setEmail($customer->getEmail()) ;
            // $buyer->setAddress() ;
            $data->setBuyer($buyer) ;
            // $orderShippingAddress = $order->getShippingAddress() ;
            $shipping = new ShippingSerializerLegacy() ;
            $shipping->setShippingMethod('PACKAGE_DELIVERY_COMPANY') ;
            $data->setShipping($shipping) ;
        }

        $data->setAmount($order->getTotal()) ;
        $data->setCurrency($order->getCurrencyCode()) ;
        $data->setUrlReturn($returnUrl);
        $data->setUrlSuccess($returnUrl);
        $data->setUrlRefused($returnUrl);
        $data->setUrlCancel($returnUrl);
        $data->setUrlError($returnUrl);
        $data->setLanguage(OrderSerializer::LANGUAGE_FR);

        $this->orderSerializer = $data ;
    }

    /**
     * @param Order $order
     *
     * @return Refund
     */
    private function processOrderRefund(Order $order): Refund
    {
        $defaultSellerUuid = getenv("REWORLD_LYRA_MARKETPLACE_SELLER_UUID");
        $referenceRemboursement = "remb".$order->getId();
        $refundItems = $this->hydrateRefundItemsFromOrder($order, $referenceRemboursement, $defaultSellerUuid);

        $refund = new Refund();
        $refund->setOrder($order->getLyraOrderUuid());
        $refund->setReference($referenceRemboursement);
        $refund->setDescription("Remboursement commande  #".$order->getNumber());
        $refund->setCurrency($order->getCurrencyCode());
        $refund->setItems($refundItems);

        return $refund;
    }

    /**
     * @param Order $order
     * @param bool $update
     * @return void
     */
    private function hydrateItemsFromOrder(Order $order, bool $update = false): void
    {
        /** @var OrderItem $item */
        foreach ($order->getItems() as $item){
            $lyraOrderItem = $this->getLyraItem($item);

            $itemSerializer = $lyraOrderItem ?? new ItemSerializer();
            if (!$update){
                if (!empty($item->getProduct()->getVendor())){
                    $vendor = $item->getProduct()->getVendor() ;
                    $sellerUuid = $vendor->getSellerUuid();
                } else {
                    $sellerUuid = getenv("REWORLD_LYRA_MARKETPLACE_SELLER_UUID");
                }

                $itemSerializer->setSeller($sellerUuid) ;
                $itemSerializer->setReference($item->getProduct()->getCode()) ;
                $itemSerializer->setDescription($this->cleanDescription($item->getProductName())) ;
                $itemSerializer->setType(ItemSerializer::TYPE_ENTERTAINMENT) ;
            }
            $itemSerializer->setAmount($item->getTotal()) ;

            if ($item->getProduct()->getVendor() !== null){
                $itemSerializer->setCommissionAmount($this->calculateCommissionForOrderItem($item));
            }

            $this->items[] = $itemSerializer ;

        }

    }

    /**
     * @param Order $order
     * @param $referenceRemboursement
     * @param $defaultSellerUuid
     *
     * @return RefundItem[]
     */
    private function hydrateRefundItemsFromOrder(Order $order, $referenceRemboursement, $defaultSellerUuid): array
    {
        $refundItems = [];
        $totalCommissionAmount = 0;

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item){
            $refundItem = new RefundItem();

            if (!empty($item->getProduct()->getVendor())){
                $vendor = $item->getProduct()->getVendor() ;
                $sellerUuid = $vendor->getSellerUuid();
            } else {
                $sellerUuid = $defaultSellerUuid;
            }

            $itemCommissionAmount = $this->calculateCommissionForOrderItem($item);
            $totalCommissionAmount+= $itemCommissionAmount;
            $itemTotal = $item->getTotal();
            $itemTotalWithoutCommission = $itemTotal - $itemCommissionAmount;

            $refundItem->setSeller($sellerUuid) ;
            $refundItem->setReference($referenceRemboursement.'_'.$item->getId());
            $refundItem->setDescription($item->getProductName());
            $refundItem->setAmount($itemTotalWithoutCommission);
            $refundItems[] = $refundItem;
        }

        //on ajoute la partie prise en charge par le gestionnaire
        $refundItem = new RefundItem();
        $refundItem->setSeller($defaultSellerUuid) ;
        $refundItem->setReference($referenceRemboursement.'_gest');
        $refundItem->setDescription("Gestionnaire");
        $refundItem->setAmount($totalCommissionAmount);
        $refundItems[] = $refundItem;

        return $refundItems;
    }

    /**
     * @throws ApiException
     */
    public function retrieveToken($token): OrderRegister
    {
        return $this->tokenApi->tokensRead($token);
    }

    /**
     * @throws ApiException
     */
    public function retrieveAlias($alias): GetTokenDetails
    {
        return $this->marketplaceApi->marketplacesAliasRead($alias, $this->marketplaceUUID);
    }

    /**
     * @param OrderSerializer $orderSerializer
     * @return array
     */
    private function getLyraOrderItemsIds(OrderSerializer $orderSerializer): array
    {
        $lyraOrderItems = $orderSerializer->getItems() ;
        $lyraOrderItemIds = [] ;

        /** @var ItemSerializer $lyraOrderItem */
        foreach ($lyraOrderItems as $lyraOrderItem){
            $lyraOrderItemIds[] = $lyraOrderItem->getUuid() ;
        }

        return $lyraOrderItemIds ;

    }


    /**
     * @param OrderItem $orderItem
     * @return mixed
     */
    private function getCommissionRateForOrderItem(OrderItem $orderItem)
    {
        /** @var Product $product */
        $product = $orderItem->getProduct() ;
        $taxon = $product->getMainTaxon() ;
        $commissionRate = $product->getTauxCommissionRm();

        if (null === $commissionRate || $commissionRate === 0){
            $commissionRate = $taxon->getEditorCommRate();
        }

        if ((null === $commissionRate || $commissionRate === 0) && $product->getVendor() !== null) {
            $commissionRate = $product->getVendor()->getTauxCommissionRm() ;
        }

        return $commissionRate ;
    }

    /**
     * @param OrderItem $orderItem
     * @return float|int
     */
    private function calculateCommissionForOrderItem(OrderItem $orderItem)
    {
        $commissionRate = $this->getCommissionRateForOrderItem($orderItem);

        return $orderItem->getTotal() * $commissionRate / 100 ;

    }

    /**
     * @param $gender
     * @return string
     */
    private function getCivilite($gender): string
    {

        if (CustomerInterface::MALE_GENDER === $gender) {
            return 'MR';
        }

        if (CustomerInterface::FEMALE_GENDER === $gender) {
            return 'ME';
        }

        return '' ;

    }

    /**
     * @param OrderItem $item
     * @return ItemSerializer|void
     */
    private function getLyraItem(OrderItem $item)
    {
        try {
            return  $this->itemsApi->itemsRead($item->getLyraItemUuid());
        } catch (Exception $e) {
            echo 'Exception when calling itemsApi->itemsRead : ', $e->getMessage(), PHP_EOL;
        }
    }

    /**
     * @param $description
     * @return string|string[]|null
     */
    public function cleanDescription($description) {
        return preg_replace("/[^-\w ]/", "", $description);
    }


}
