<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DataObject;


class OnepageControllerSuccessAction implements \Magento\Framework\Event\ObserverInterface
{

    protected $order;
    protected $logger;
    protected $_actionFlag;
    protected $_response;
    protected $_redirect;

    public function __construct(
        \Magento\Sales\Model\Order $order, 
        \Magento\Framework\App\Response\RedirectInterface $redirect, 
        \Magento\Framework\App\ActionFlag $actionFlag, 
        \Psr\Log\LoggerInterface $logger_interface, 
        \Magento\Framework\App\ResponseInterface $response
        ) {
            $this->order = $order;
            $this->logger = $logger_interface;
    
            $this->_redirect = $redirect;
            $this->_response = $response;
    
            $this->_actionFlag = $actionFlag;
        }


    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {

        $orderId = $observer->getEvent()->getOrderIds();
        $order = $this->order->load($orderId[0]);

        if ($order->getPayment()->getMethod() == 'sixplus1_banorte') {

            //Flag solo 3D Secure para version Open Source
            $solo3d_sincybersource = true;

            if ($solo3d_sincybersource == true) {
                $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $this->_redirect->redirect($this->_response, $_SESSION['banorte_redirect_url']);

            }else{

               // versión Pro

            }

          

            

        } 

        return $this;



        
        




    }
}

