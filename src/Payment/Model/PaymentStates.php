<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Payment\Model;

use Akki\SyliusPayumLyraMarketplacePlugin\Exception\InvalidArgumentException;

/**
 * Class PaymentStates
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Payment\Model
 */
final class PaymentStates
{
    public const STATE_CREATED         = 'CREATED';
    public const STATE_PENDING         = 'PENDING';
    public const STATE_SUCCEEDED       = 'SUCCEEDED';
    public const STATE_CANCELLED       = 'CANCELLED';
    public const STATE_FAILED          = 'FAILED';
    public const STATE_ABANDONED       = 'ABANDONED';

    /**
     * Returns all the states.
     *
     * @return array
     */
    public static function getStates(): array
    {
        return [
            self::STATE_CREATED,
            self::STATE_PENDING,
            self::STATE_SUCCEEDED,
            self::STATE_CANCELLED,
            self::STATE_FAILED,
            self::STATE_ABANDONED,
        ];
    }

    /**
     * Returns whether the given state is valid.
     *
     * @param string $state
     * @param bool $throwException
     *
     * @return bool
     */
    public static function isValidState(string $state, bool $throwException = true): bool
    {
        if (in_array($state, self::getStates(), true)) {
            return true;
        }

        if ($throwException) {
            throw new InvalidArgumentException("Invalid payment states '$state'.");
        }

        return false;
    }

    /**
     * Returns the notifiable states.
     *
     * @return array
     */
    public static function getNotifiableStates(): array
    {
        return [
            self::STATE_PENDING,
            self::STATE_FAILED,
        ];
    }

    /**
     * Returns whether the given state is a notifiable state.
     *
     * @param string $state
     *
     * @return bool
     */
    public static function isNotifiableState(string $state): bool
    {
        return in_array($state, self::getNotifiableStates(), true);
    }

    /**
     * Returns the deletable states.
     *
     * @return array
     */
    public static function getDeletableStates(): array
    {
        return [
            self::STATE_CREATED,
            self::STATE_CANCELLED,
            self::STATE_FAILED,
        ];
    }

    /**
     * Returns whether the given state is a deletable state.
     *
     * @param string|null $state
     *
     * @return bool
     */
    public static function isDeletableState(?string $state): bool
    {
        return is_null($state) || in_array($state, self::getDeletableStates(), true);
    }

    /**
     * Returns the paid states.
     *
     * @return array
     */
    public static function getPaidStates(): array
    {
        return [
            self::STATE_SUCCEEDED
        ];
    }

    /**
     * Returns whether the given state is a paid state.
     *
     * @param string $state
     *
     * @return bool
     */
    public static function isPaidState(string $state): bool
    {
        return in_array($state, self::getPaidStates(), true);
    }

    /**
     * Returns the canceled states.
     *
     * @return array
     */
    public static function getCanceledStates(): array
    {
        return [
            self::STATE_CANCELLED,
            self::STATE_FAILED,
            self::STATE_ABANDONED,
        ];
    }

    /**
     * Returns whether the state has changed
     * from a non paid state to a paid state.
     *
     * @param array $cs The persistence change set
     *
     * @return bool
     */
    public static function hasChangedToPaid(array $cs): bool
    {
        return self::assertValidChangeSet($cs)
            && !self::isPaidState($cs[0])
            && self::isPaidState($cs[1]);
    }

    /**
     * Returns whether the state has changed
     * from a paid state to a non paid state.
     *
     * @param array $cs The persistence change set
     *
     * @return bool
     */
    public static function hasChangedFromPaid(array $cs): bool
    {
        return self::assertValidChangeSet($cs)
            && self::isPaidState($cs[0])
            && !self::isPaidState($cs[1]);
    }

    /**
     * Returns whether the change set is valid.
     *
     * @param array $cs
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    private static function assertValidChangeSet(array $cs): bool
    {
        if (
            array_key_exists(0, $cs) &&
            array_key_exists(1, $cs) &&
            (is_null($cs[0]) || in_array($cs[0], self::getStates(), true)) &&
            (is_null($cs[1]) || in_array($cs[1], self::getStates(), true))
        ) {
            return true;
        }

        throw new InvalidArgumentException("Unexpected order state change set.");
    }
}