<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_RewardsGraphQl
 */


declare(strict_types=1);

use Amasty\Rewards\Api\Data\RuleInterface;
use Amasty\Rewards\Api\RuleRepositoryInterface;
use Magento\Framework\App\Request\DataPersistor;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var DataPersistor $persistor */
$persistor = Bootstrap::getObjectManager()->get(DataPersistorInterface::class);
/** @var RuleRepositoryInterface $repository */
$repository = $objectManager->create(RuleRepositoryInterface::class);

/** @var RuleInterface $rule */
$ruleId = $persistor->get('rewards_rule_newsletter_id');
if ($ruleId) {
    $repository->deleteById((int)$ruleId);
}
$persistor->clear('rewards_rule_newsletter_id');
