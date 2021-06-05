<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rewards
 */


namespace Amasty\Rewards\Model;

class Transport
{
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $customerResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Amasty\Rewards\Model\Config
     */
    private $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $helper;

    /**
     * @var Date
     */
    private $date;

    public function __construct(
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Amasty\Rewards\Model\Date $date,
        \Amasty\Rewards\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Amasty\Rewards\Helper\Data $helper
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
        $this->storeManager = $storeManagerInterface;
        $this->config = $config;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->date = $date;
    }

    /**
     * @param int|float $amount
     * @param int $customerId
     * @param string $action
     *
     * @return $this
     */
    public function sendRewardsEarningNotification($amount, $customerId, $action)
    {
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $customerId);

        $isEnableNotification = $customer->getAmrewardsEarningNotification();

        $store = $this->storeManager->getStore($customer->getStoreId());

        if (!$isEnableNotification || !$this->config->getSendEarnNotification($store)
            || !$this->config->isEnabled(
                $store
            )
        ) {
            return $this;
        }

        $template = $this->config->getEarnTemplate($store);

        $tplVars = [
            'store' => $store,
            'customer' => $customer,
            'earned_reward' => sprintf("%.2f", $amount),
            'action' => $this->getAction($action),
            'rewards_balance_change_date' => $this->date->convertDate(null, $store->getCode())
        ];

        try {
            $this->send($template, $customer, $tplVars, $store);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $this;
    }

    /**
     * @param array $expirationRows
     * @param int $day
     *
     * @return $this
     */
    public function sendExpireNotification($expirationRows, $day)
    {
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, current($expirationRows)['customer_id']);

        $store = $this->storeManager->getStore($customer->getStoreId());
        if (!$this->config->isEnabled($store)) {
            return $this;
        }

        $total = 0;
        $deadlines = [];

        foreach ($expirationRows as $expirationRow) {
            if ($expirationRow['days_left'] <= $day) {
                $total += $expirationRow['points'];
                $expirationRow['earn_date'] = $this->date->convertDate($expirationRow['earn_date'], $store->getCode());
                $expirationRow['expiration_date'] =
                    $this->date->convertDate($expirationRow['expiration_date'], $store->getCode());

                $deadlines[] = $expirationRow;
            }
        }

        $template = $this->config->getExpireTemplate($store);

        $tplVars = [
            'store' => $store,
            'customer' => $customer,
            'total_rewards' => $total,
            'deadlines' => $deadlines
        ];

        try {
            $this->send($template, $customer, $tplVars, $store);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $this;
    }

    /**
     * @param mixed $template
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $vars
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @throws \Magento\Framework\Exception\MailException
     */
    private function send(
        $template,
        \Magento\Customer\Model\Customer $customer,
        $vars,
        \Magento\Store\Api\Data\StoreInterface $store
    ) {
        $this->transportBuilder->setTemplateIdentifier(
            $template
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $store->getId()]
        )->setTemplateVars(
            $vars
        )->setFrom(
            $this->config->getEmailSender($store)
        )->addTo(
            $customer->getEmail()
        );

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
    }

    /**
     * @param string $action
     *
     * @return string
     */
    private function getAction($action)
    {
        $actionsList = $this->helper->getActions();

        return isset($actionsList[$action]) ? $actionsList[$action] : $action;
    }
}
