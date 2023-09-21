<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Block\Payment;

use Magento\Store\Model\StoreManagerInterface;

use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\Controller\ResultFactory;
use Sixplus1\Banorte\Api\Sixplus1RestClientInterfaceFactory;


class Payment extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    const RESPUESTA_CODIGO_APROVADA = 'A';

    protected $resultPageFactory;

    protected $checkoutSession;
    protected $orderRepository;
    protected $logger;
    protected $_invoiceService;
    protected $transactionBuilder;
    protected $Sixplus1RestClientFactory;
    protected $messageManager;

    protected $orderFactory;

    protected $_checkoutSessionFactory;

    protected $_coreRegistry;

    protected $resultRedirectFactory;


    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger_interface,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        Sixplus1RestClientInterfaceFactory $Sixplus1RestClientFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        \Magento\Framework\Registry $coreRegistry,
        ResultFactory $resultRedirectFactory,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger_interface;        
        $this->_invoiceService = $invoiceService;
        $this->transactionBuilder = $transactionBuilder;

        $this->messageManager = $messageManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->Sixplus1RestClientFactory = $Sixplus1RestClientFactory;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_checkoutSessionFactory = $checkoutSessionFactory->create();
        $this->_coreRegistry = $coreRegistry;
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context, $data);
    }
    
    /**
     * getParams
     *
     * @return void
     */
    public function getParams()
    {
        return $this->_coreRegistry->registry('banorte_payment_payworks_params');
    }


    
    /**
     * procesaPayworks
     *
     * @return void
     */
    public function procesaPayworks()
    {
        $params = $this->getCheckoutSession()->getParametrosFinalesPayworks();

        try {
            $client = $this->Sixplus1RestClientFactory->create();
            $response = $client->sendRequest($params);
            $responseBody = $response->getBody();
            $headers = $response->getHeaders();

            if($headers['Resultado_payw'] != self::RESPUESTA_CODIGO_APROVADA){

                if ($order->canCancel()) {
                    $order->cancel();
                    $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED, __('El banco del cliente rechazó la transaccion.'));
                    $this->orderRepository->save($order);
                }

                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');

            }else{
                $timestamp_rspcte = strtotime($headers['Fecha_rsp_cte']);
                $order->setResultadoPayw($headers['Resultado_payw']);
                $order->setIdAfiliacionPayw($headers['Id_afiliacion']);
                $order->setFechaRspCtePayw($timestamp_rspcte);
                $order->setCodigoAutPayw($headers['Codigo_aut']);
                $order->setReferenciaPayw($headers['Referencia']);
                $order->save();
                $requiresInvoice = true;

                $invoiceCollection = $order->getInvoiceCollection();
                if ( $invoiceCollection->count() > 0 ) {

                    foreach ($invoiceCollection as $invoice ) {
                        if ( $invoice->getState() == Invoice::STATE_OPEN) {
                            $invoice->setState(Invoice::STATE_PAID);
                            $invoice->setTransactionId($headers['Codigo_aut']);
                            $invoice->pay()->save();
                            $requiresInvoice = false;
                            break;
                        }
                    }
                }
                if ( $requiresInvoice ) {
                    $invoice = $this->_invoiceService->prepareInvoice($order);
                    $invoice->setTransactionId($headers['Codigo_aut']);
                    $invoice->pay()->save();
                }
                $payment = $order->getPayment();                                
                $payment->setAmountPaid($_parametros['MONTO']);
                $payment->setIsTransactionPending(false);
                $payment->save();

                unset($_SESSION['banorte_parametros_3dsecure']);
                unset($_SESSION['banorte_parametros_payworks']);
                unset($_SESSION['banorte_respuesta_cybersource']);
                unset($_SESSION['banorte_redirect_url']);
                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
                        
            }

                    
                    

        } catch (\Exception $e) {
            $this->logger->debug($e);
            throw new \Magento\Framework\Validator\Exception(__($this->error($e)));
        }
    }
    
    /**
     * getCheckoutSession
     *
     * @return void
     */
    public function getCheckoutSession() 
    {
        return $this->_checkoutSessionFactory;
    }    





}

