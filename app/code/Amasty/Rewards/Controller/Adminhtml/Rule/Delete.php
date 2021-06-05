<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rewards
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Rewards\Controller\Adminhtml\Rule;

use Magento\Framework\App\ResponseInterface;

class Delete extends \Amasty\Rewards\Controller\Adminhtml\Rule
{
    /**
     * Dispatch request
     *
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $itemId = $this->getRequest()->getParam('id');
        if (!$itemId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a item to delete.'));
            $this->_redirect('*/*/');
            return;
        }
        try {
            $this->ruleRepository->deleteById($itemId);
            $this->messageManager->addSuccessMessage(__('You deleted the item.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->_redirect('*/*/');
    }
}
