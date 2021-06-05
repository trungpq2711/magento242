<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_RewardsGraphQl
 */


declare(strict_types=1);

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var GuestCartManagementInterface $guestCartManagement */
$guestCartManagement = Bootstrap::getObjectManager()->get(GuestCartManagementInterface::class);

/** @var CartRepositoryInterface $cartRepository */
$cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);

/** @var MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId */
$maskedQuoteIdToQuoteId = Bootstrap::getObjectManager()->get(MaskedQuoteIdToQuoteIdInterface::class);

$cartHash = $guestCartManagement->createEmptyCart();
$cartId = $maskedQuoteIdToQuoteId->execute($cartHash);
$cart = $cartRepository->get($cartId);
$cart->setReservedOrderId('rewards_quote_guest');
$cartRepository->save($cart);
