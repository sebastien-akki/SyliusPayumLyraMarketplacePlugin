<?php

declare(strict_types=1);

namespace Akki\SyliusPayumLyraMarketplacePlugin\Extension;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\GetHumanRefundStatus;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\NotifyRefund;
use Doctrine\Common\Collections\Collection;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Generic;
use SM\Factory\FactoryInterface;
use SM\SMException;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\OrderPaymentTransitions;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Payment\Model\PaymentInterface as PaymentInterfaceAlias;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;
use Webmozart\Assert\Assert;

final class UpdatePaymentStateExtension implements ExtensionInterface
{
    /** @var FactoryInterface */
    private $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function onPreExecute(Context $context): void
    {
    }

    public function onExecute(Context $context): void
    {
    }

    /**
     * @param Context $context
     *
     * @return void
     *
     * @throws SMException
     */
    public function onPostExecute(Context $context): void
    {
        $previousStack = $context->getPrevious();
        $previousStackSize = count($previousStack);

        if ($previousStackSize > 0) {
            return;
        }

        $request = $context->getRequest();

        if (!$request instanceof Generic) {
            return;
        }

        if ( !$request instanceof NotifyRefund) {
            return;
        }

        $payment = $request->getFirstModel();

        if (!$payment instanceof PaymentInterface) {
            return;
        }

        if (null !== $context->getException()) {
            return;
        }

        $context->getGateway()->execute($status = new GetHumanRefundStatus($payment));
        $value = $status->getValue();
        if (PaymentInterfaceAlias::STATE_UNKNOWN !== $value && $payment->getState() !== $value) {
            $this->updatePaymentState($payment, $value);
            $this->verifyRefund($payment->getOrder());
        }
    }

    /**
     * @param PaymentInterface $payment
     * @param string $nextState
     *
     * @throws SMException
     */
    private function updatePaymentState(PaymentInterface $payment, string $nextState): void
    {
        $stateMachine = $this->factory->get($payment, PaymentTransitions::GRAPH);

        /** @var StateMachineInterface $stateMachine */
        Assert::isInstanceOf($stateMachine, StateMachineInterface::class);

        if (null !== $transition = $stateMachine->getTransitionToState($nextState)) {
            $stateMachine->apply($transition);
        }
    }

    /**
     * @param BaseOrderInterface $order
     *
     * @return void
     *
     * @throws SMException
     */
    private function verifyRefund(BaseOrderInterface $order): void
    {
        /** @var OrderInterface $order */
        Assert::isInstanceOf($order, OrderInterface::class);

        $stateMachine = $this->factory->get($order, OrderPaymentTransitions::GRAPH);
        $targetTransition = $this->getTargetTransition($order);

        if (null !== $targetTransition) {
            $this->applyTransition($stateMachine, $targetTransition);
        }
    }

    /**
     * @param OrderInterface $order
     *
     * @return string|null
     */
    private function getTargetTransition(OrderInterface $order): ?string
    {
        $refundedPaymentTotal = 0;
        $refundedPayments = $this->getPaymentsWithStateRefunded($order);

        foreach ($refundedPayments as $payment) {
            $refundedPaymentTotal += $payment->getAmount();
        }

        if ($refundedPaymentTotal >= $order->getTotal() && 0 < $refundedPayments->count()) {
            return OrderPaymentTransitions::TRANSITION_REFUND;
        }

        if (0 < $refundedPaymentTotal && $refundedPaymentTotal < $order->getTotal()) {
            return OrderPaymentTransitions::TRANSITION_PARTIALLY_REFUND;
        }

        return null;
    }

    /**
     * @param \SM\StateMachine\StateMachineInterface $stateMachine
     * @param string $transition
     *
     * @return void
     *
     * @throws SMException
     */
    private function applyTransition(\SM\StateMachine\StateMachineInterface $stateMachine, string $transition): void
    {
        if ($stateMachine->can($transition)) {
            $stateMachine->apply($transition);
        }
    }

    /**
     * @param OrderInterface $order
     *
     * @return Collection
     */
    private function getPaymentsWithStateRefunded(OrderInterface $order): Collection
    {
        $state = PaymentInterfaceAlias::STATE_REFUNDED;

        return $order->getPayments()->filter(function (PaymentInterface $payment) use ($state) {
            return $state === $payment->getState();
        });
    }
}
