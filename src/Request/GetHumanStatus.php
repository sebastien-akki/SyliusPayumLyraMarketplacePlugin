<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Request;

use Akki\SyliusPayumLyraMarketplacePlugin\Payment\Model\PaymentStates;
use Payum\Core\Request\BaseGetStatus;

/**
 * Class GetHumanStatus
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Request
 */
class GetHumanStatus extends BaseGetStatus
{
    /**
     * {@inheritdoc}
     */
    public function markNew()
    {
        $this->status = PaymentStates::STATE_CREATED;
    }

    /**
     * {@inheritdoc}
     */
    public function isNew()
    {
        return $this->status === PaymentStates::STATE_CREATED;
    }

    /**
     * {@inheritdoc}
     */
    public function markPending()
    {
        $this->status = PaymentStates::STATE_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function isPending()
    {
        return $this->status === PaymentStates::STATE_PENDING;
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
        $this->status = PaymentStates::STATE_CANCELLED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCanceled()
    {
        return $this->status === PaymentStates::STATE_CANCELLED;
    }

    /**
     * {@inheritdoc}
     */
    public function markFailed()
    {
        $this->status = PaymentStates::STATE_FAILED;
    }

    /**
     * {@inheritdoc}
     */
    public function isFailed()
    {
        return $this->status === PaymentStates::STATE_FAILED;
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
        return $this->status === PaymentStates::STATE_REFUNDED;
    }

    /**
     * {@inheritdoc}
     */
    public function markRefunded()
    {
        $this->status = PaymentStates::STATE_REFUNDED;
    }

    /**
     * {@inheritdoc}
     */
    public function markUnknown()
    {
        // TODO: Implement method.
    }

    /**
     * {@inheritdoc}
     */
    public function isUnknown()
    {
        // TODO: Implement method.
    }
}