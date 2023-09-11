<?php
declare(strict_types=1);


namespace Akki\SyliusPayumLyraMarketplacePlugin\StateMachine;

use Akki\SyliusPayumLyraMarketplacePlugin\Request\Api\ValidatePayment;
use Payum\Core\Payum;
use SM\Factory\Factory;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentInterface as PaymentInterfaceAlias;
use Sylius\Component\Payment\PaymentTransitions;

final class ValidatePaymentOrderProcessor extends AbstractOrderProcessor
{
    private $smFactory;

    public function __construct(
        Payum   $payum,
        Factory $smFactory
    )
    {
        parent::__construct($payum);

        $this->smFactory = $smFactory;
    }

    public function __invoke(PaymentInterface $payment): void
    {
        if (PaymentInterfaceAlias::STATE_NEW !== $payment->getState()) {
            return;
        }

        $gatewayName = $this->getGatewayNameFromPayment($payment);

        if (null === $gatewayName) {
            return;
        }

        $gateway = $this->payum->getGateway($gatewayName);
        $token = $this->payum->getTokenFactory()->createToken($gatewayName, $payment, 'sylius_shop_order_thank_you');

        $token->setDetails($payment);

        $validatePaymentRequest = new ValidatePayment($token);
        $gateway->execute($validatePaymentRequest);

        $stateMachine = $this->smFactory->get($payment, PaymentTransitions::GRAPH);

        if (true === $stateMachine->can(PaymentTransitions::TRANSITION_CREATE)) {
            $stateMachine->apply(PaymentTransitions::TRANSITION_CREATE);
        }

        if (true === $stateMachine->can(PaymentTransitions::TRANSITION_COMPLETE)) {
            $stateMachine->apply(PaymentTransitions::TRANSITION_COMPLETE);
        }
    }

}