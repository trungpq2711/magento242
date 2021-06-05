<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rewards
 */


namespace Amasty\Rewards\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Amasty\Rewards\Model\Config\Source\RedemptionLimitTypes;

class CheckoutRewardsManagement implements \Amasty\Rewards\Api\CheckoutRewardsManagementInterface
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Amasty\Rewards\Model\Config
     */
    private $config;

    /**
     * @var \Amasty\Rewards\Model\Quote
     */
    private $rewardsQuote;

    /**
     * @var \Amasty\Rewards\Helper\Data
     */
    private $helper;

    /**
     * @var \Amasty\Rewards\Api\RewardsRepositoryInterface
     */
    private $rewardsRepository;

    public function __construct(
        \Amasty\Rewards\Model\Config $config,
        \Amasty\Rewards\Model\Quote $rewardsQuote,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Amasty\Rewards\Api\RewardsRepositoryInterface $rewardsRepository,
        \Amasty\Rewards\Helper\Data $helper
    ) {
        $this->config = $config;
        $this->rewardsQuote = $rewardsQuote;
        $this->quoteRepository = $quoteRepository;
        $this->rewardsRepository = $rewardsRepository;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function set($cartId, $usedPoints)
    {
        if (!$usedPoints || $usedPoints < 0) {
            throw new LocalizedException(__('Points "%1" not valid.', $usedPoints));
        }

        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $minPoints = $this->config->getMinPointsRequirement($quote->getStoreId());

        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $pointsLeft = $this->rewardsRepository->getCustomerRewardBalance($quote->getCustomerId());

        if ($minPoints && $pointsLeft < $minPoints) {
            throw new LocalizedException(
                __('You need at least %1 points to pay for the order with reward points.', $minPoints)
            );
        }

        try {
            if ($usedPoints > $pointsLeft) {
                throw new LocalizedException(__('Too much point(s) used.'));
            }

            $pointsData = $this->limitValidate($quote, $usedPoints);
            $usedPoints = abs($pointsData['allowed_points']);
            $itemsCount = $quote->getItemsCount();

            if ($itemsCount) {
                $this->collectCurrentTotals($quote, $usedPoints);

                $this->rewardsQuote->addReward(
                    $quote->getId(),
                    $quote->getData('amrewards_point')
                );
            }
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        $pointsData['allowed_points'] = $quote->getData('amrewards_point');
        $usedNotice = __('You used %1 point(s).', $pointsData['allowed_points']);
        $pointsData['notice'] = $pointsData['notice'] . ' ' . $usedNotice;

        return $pointsData;
    }

    /**
     * {@inheritdoc}
     */
    public function collectCurrentTotals(\Magento\Quote\Model\Quote $quote, $usedPoints)
    {
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->setData('amrewards_point', $usedPoints);
        $quote->setDataChanges(true);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param float $usedPoints
     *
     * @return array
     */
    private function limitValidate(\Magento\Quote\Model\Quote $quote, $usedPoints)
    {
        $pointsData['allowed_points'] = $usedPoints;
        $pointsData['notice'] = '';
        $isEnableLimit = $this->config->isEnableLimit($quote->getStoreId());

        if ($isEnableLimit == RedemptionLimitTypes::LIMIT_AMOUNT) {
            $limitAmount = $this->config->getRewardAmountLimit($quote->getStoreId());

            if ($usedPoints > $limitAmount) {
                $pointsData['allowed_points'] = $limitAmount;
                $pointsData['notice'] =
                    __('Number of redeemed reward points cannot exceed %1 for this order.', $limitAmount);
            }
        } elseif ($isEnableLimit == RedemptionLimitTypes::LIMIT_PERCENT) {
            $limitPercent = $this->config->getRewardPercentLimit($quote->getStoreId());
            $subtotal = $quote->getSubtotal();
            $allowedPercent = round(($subtotal / 100 * $limitPercent) / $quote->getBaseToQuoteRate(), 2);
            $rate = $this->helper->getPointsRate();
            $basePoints = $usedPoints / $rate;

            if ($basePoints > $allowedPercent) {
                $pointsData['allowed_points'] = $allowedPercent * $rate;
                $pointsData['notice'] =
                    __('Number of redeemed reward points cannot exceed %1 '
                        . '% of cart subtotal excluding tax for this order.', $limitPercent);
            }
        }

        return $pointsData;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $itemsCount = $quote->getItemsCount();

        if ($itemsCount) {
            $this->collectCurrentTotals($quote, 0);
        }

        $this->rewardsQuote->addReward(
            $quote->getId(),
            0
        );
    }
}
