<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Request;

use Akki\SyliusPayumLyraMarketplacePlugin\Payment\Model\RefundStates;
use Payum\Core\Request\BaseGetStatus;
use Sylius\Component\Payment\Model\PaymentInterface as PaymentInterfaceSylius;

/**
 * Class GetHumanRefundStatus
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Request
 */
class GetHumanRefundStatus extends BaseGetStatus
{

    /**
     * {@inheritdoc}
     */
    public function markNew()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function isNew()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function markPending()
    {
        $this->status = RefundStates::STATE_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function isPending()
    {
        return $this->status === RefundStates::STATE_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function markSuspended()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function isSuspended()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function markExpired()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function markCanceled()
    {
        $this->status = RefundStates::STATE_CANCELLED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCanceled()
    {
        return $this->status === RefundStates::STATE_CANCELLED;
    }

    /**
     * {@inheritdoc}
     */
    public function markFailed()
    {
        $this->status = RefundStates::STATE_FAILED;
    }

    /**
     * {@inheritdoc}
     */
    public function isFailed()
    {
        return $this->status === RefundStates::STATE_FAILED;
    }

    /**
     * {@inheritdoc}
     */
    public function markCaptured()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function isCaptured()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function markAuthorized()
    {
        // TODO: Implement method.
    }

    /**
     * @inheritDoc
     */
    public function isPayedout()
    {
        // TODO: Implement isPayedout() method.
    }

    /**
     * @inheritDoc
     */
    public function markPayedout()
    {
        // TODO: Implement markPayedout() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isRefunded()
    {
        return $this->status === RefundStates::STATE_REFUNDED;
    }

    /**
     * {@inheritdoc}
     */
    public function markRefunded()
    {
        $this->status = RefundStates::STATE_REFUNDED;
    }

    /**
     * {@inheritdoc}
     */
    public function markUnknown()
    {
        return $this->status = PaymentInterfaceSylius::STATE_UNKNOWN;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnknown()
    {
        return $this->status === PaymentInterfaceSylius::STATE_UNKNOWN;
    }
}