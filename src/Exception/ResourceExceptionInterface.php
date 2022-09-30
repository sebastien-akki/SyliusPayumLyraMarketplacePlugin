<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Exception;

/**
 * Interface ResourceExceptionInterface
 * @package Akki\SyliusPayumLyraMarketplacePlugin\Exception
 */
interface ResourceExceptionInterface
{
    /**
     * Returns the message.
     *
     * @return string
     */
    public function getMessage(): string;
}