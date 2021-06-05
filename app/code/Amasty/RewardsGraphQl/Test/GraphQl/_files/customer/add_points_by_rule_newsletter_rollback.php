<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_RewardsGraphQl
 */


declare(strict_types=1);

use Amasty\Rewards\Api\HistoryRepositoryInterface;
use Amasty\Rewards\Api\RewardsRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var DataPersistorInterface $persistor */
$persistor = Bootstrap::getObjectManager()->get(DataPersistorInterface::class);

/** @var $repository RewardsRepositoryInterface */
$repository = $objectManager->create(RewardsRepositoryInterface::class);

/** @var $historyRepository HistoryRepositoryInterface */
$historyRepository = $objectManager->create(HistoryRepositoryInterface::class);

/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = Bootstrap::getObjectManager()->create(CustomerRepositoryInterface::class);

$addedId = $persistor->get('rewards_added_newsletter_id');
if ($addedId) {
    $repository->deleteById((int)$addedId);
}
$persistor->clear('rewards_added_newsletter_id');

$customer = $customerRepository->get('rewardspoints@amasty.com');
$customerId = $customer->getId();

$actionId = $persistor->get('rewards_rule_newsletter_id');
$history = $historyRepository->getLastActionByCustomerId($customerId, $actionId);
$historyRepository->delete($history);
