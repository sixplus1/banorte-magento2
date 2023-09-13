<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Block\Payment;

use Magento\Store\Model\StoreManagerInterface;
use \Sixplus1\Banorte\Helper\Increment;

class Redirect extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;


    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        Increment $increment,
        StoreManagerInterface $storeManager,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->increment = $increment;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    
    /**
     * incrementId
     *
     * @param  mixed $websiteId
     * @return void
     */
    public function incrementId($websiteId = null){

        if ($websiteId === null) {
            $websiteId = (int) $this->storeManager->getStore()->getWebsiteId();
        }
        $incrementid = $this->increment->getNextAutoincrementOrder();

        return $incrementid;

        

    }

}

