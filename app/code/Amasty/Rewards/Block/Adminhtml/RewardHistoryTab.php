<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rewards
 */


namespace Amasty\Rewards\Block\Adminhtml;

use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Customer\Controller\RegistryConstants;

class RewardHistoryTab extends TabWrapper implements TabInterface
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Reward Points History');
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('amasty_rewards/rewards/history/', ['_current' => true]);
    }
}
