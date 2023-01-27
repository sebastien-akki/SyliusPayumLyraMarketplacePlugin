<?php

declare(strict_types=1);

namespace Akki\SyliusPayumLyraMarketplacePlugin\Extension;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\GetHumanRefundStatus;
use Akki\SyliusPayumLyraMarketplacePlugin\Request\NotifyRefund;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Generic;
use SM\Factory\FactoryInterface;
use SM\SMException;
use Sylius\Component\Payment\Model\PaymentInterface;
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
        if (PaymentInterface::STATE_UNKNOWN !== $value && $payment->getState() !== $value) {
            $this->updatePaymentState($payment, $value);
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
}
