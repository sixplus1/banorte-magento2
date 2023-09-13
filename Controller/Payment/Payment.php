<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\App\ResourceConnection;

class Payment extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    const RESPUESTA_CODIGO_APROVADA = 'A';

    protected $resultPageFactory;

    protected $checkoutSession;
    protected $orderRepository;
    protected $logger;
    protected $_invoiceService;
    protected $transactionBuilder;
    protected $_httpClientFactory;
    protected $messageManager;

    protected $orderFactory;

    protected $_checkoutSessionFactory;

    protected $_coreRegistry;

    protected $layoutFactory;

    protected $resourceConnection;

    protected $encryptor;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,

        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger_interface,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger_interface;        
        $this->_invoiceService = $invoiceService;
        $this->transactionBuilder = $transactionBuilder;

        $this->messageManager = $messageManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_httpClientFactory = $httpClientFactory;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_checkoutSessionFactory = $checkoutSessionFactory->create();
        $this->_coreRegistry = $coreRegistry;
        $this->layoutFactory = $layoutFactory;
        $this->resourceConnection = $resourceConnection;
        $this->encryptor = $encryptor;
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     * @link https://magento.stackexchange.com/questions/253414/magento-2-3-upgrade-breaks-http-post-requests-to-custom-module-endpoint
     *
     * @return InvalidRequestException|null
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        if($this->getRequest()->isPost()) {
            $datos_3d_secure = $this->getRequest()->getParams(); 
            $_3d_eci = isset($datos_3d_secure['ECI']) ? $datos_3d_secure['ECI'] : "";
            $_3d_xid = isset($datos_3d_secure['XID']) ? $datos_3d_secure['XID'] : "";
            $_3d_cavv = isset($datos_3d_secure['CAVV']) ? $datos_3d_secure['CAVV'] : "";
            $_3d_status = isset($datos_3d_secure['Status']) ? $datos_3d_secure['Status'] : "";
            $_3d_reference = isset($datos_3d_secure['REFERENCE3D']) ? $datos_3d_secure['REFERENCE3D'] : "";
            $_3d_message = isset($datos_3d_secure['MESSAGE']) ? $datos_3d_secure['MESSAGE'] : "";

            if ($_3d_status != 200) {
                try {
                    $order_id = $this->checkoutSession->getLastOrderId();
                    $quote_id = $this->checkoutSession->getLastQuoteId();
                    $this->checkoutSession->setLastSuccessQuoteId($quote_id);
                    $order = $this->orderFactory->create()->loadByIncrementId($_3d_reference);
                    $customer_id = $order->getExtCustomerId();

                    if ($order->canCancel()) {
                        $order->cancel();
                        $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED, __('Autenticación de 3D Secure fallida.'));
                        $this->orderRepository->save($order);
                    }


                } catch (\Exception $e) {
                    $this->logger->error('#ERROR AL CANCELAR ', array('message' => $e->getMessage(), 'code' => $e->getCode(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()));

                }
                $this->messageManager->addError("La transacción fue rechazada por su banco y su tarjeta NO fue cargada. También el pedido en nuestro sistema ha sido cancelado de manera automática. Si recibió algún correo electrónico de confirmación de pedido por nuestra parte, pronto recibirá otro confirmando la cancelación del mismo. En caso de tener alguna duda llámenos.");
                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');

            } else {

                try { 
                    $order = $this->orderFactory->create()->loadByIncrementId($_3d_reference);        
                    $customer_id = $order->getExtCustomerId();
                    $ccType = $order->getPayment()->getCcType();
                    $ccExpMonth = $order->getPayment()->getCcExpMonth();
                    $ccExpYear = $order->getPayment()->getCcExpYear();
                    $_monto_pedido = (float) $order->getGrandTotal();
                    $_anioExpira = substr($ccExpYear, 2);
                    $_mesExpira=sprintf("%02s",$ccExpMonth);
                    $fechaExpiracion=$_mesExpira.$_anioExpira;
                    $fechaExpiracion_3D = $_mesExpira."/".$_anioExpira;
                    
                    //Se leen de la DB y se destruyen
                    $cc_detalles = $this->encuentraTransaccion($order->getIncrementId());
                    $ccNumber = (string) $cc_detalles['transaccion'];
                    $ccCid = (string) $cc_detalles['transaccion_caduca'];
                    $ccNumber = $this->encryptor->decrypt($ccNumber);
                    $ccCid = $this->encryptor->decrypt($ccCid);
                    
                    $_parametros = array (
                        'ID_AFILIACION'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_id_afiliacion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                        'USUARIO'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_usuario', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                        'CLAVE_USR'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_usuario_clave', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                        'CMD_TRANS'   => 'VENTA',
                        'ID_TERMINAL'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_id_terminal', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                        'MONTO'   => $_monto_pedido,
                        'MODO'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_modo', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                        'NUMERO_CONTROL'   => $order->getIncrementId(),
                        'REF_CLIENTE1'   => 'SIXPLUS1 BANORTE MAGENTO2', 
                        'NUMERO_TARJETA'   => $ccNumber,
                        'FECHA_EXP'   => $fechaExpiracion,
                        'CODIGO_SEGURIDAD'   => $ccCid,
                        'MODO_ENTRADA'   => 'MANUAL',
                        'IDIOMA_RESPUESTA'   => 'ES'
                        );

                    $_numero_control = "3D".$_parametros['NUMERO_CONTROL'];

                    if ($_3d_xid == "") {
                        $params = array (
                            'ID_AFILIACION'   => $_parametros['ID_AFILIACION'],
                            'USUARIO'   => $_parametros['USUARIO'],
                            'CLAVE_USR'   => $_parametros['CLAVE_USR'],
                            'CMD_TRANS'   => $_parametros['CMD_TRANS'],
                            'ID_TERMINAL'   => $_parametros['ID_TERMINAL'],
                            'MONTO'   => $_parametros['MONTO'],
                            'MODO'   => $_parametros['MODO'],
                            'NUMERO_CONTROL'   => $_numero_control."XIDVACIO",
                            'REF_CLIENTE1'   => $_parametros['REF_CLIENTE1'],
                            'NUMERO_TARJETA'   => $_parametros['NUMERO_TARJETA'],
                            'FECHA_EXP'   => $_parametros['FECHA_EXP'],
                            'CODIGO_SEGURIDAD'   => $_parametros['CODIGO_SEGURIDAD'],
                            'MODO_ENTRADA'   => $_parametros['MODO_ENTRADA'],
                            'IDIOMA_RESPUESTA'   => $_parametros['IDIOMA_RESPUESTA'],
                            'ESTATUS_3D' => $_3d_status,
                            'ECI' => $_3d_eci,
                            'CAVV' => $_3d_cavv,
                            'VERSION_3D' => "2"
                        );
                    
                    } elseif ($_3d_cavv == ""){
                        $params = array (
                            'ID_AFILIACION'   => $_parametros['ID_AFILIACION'],
                            'USUARIO'   => $_parametros['USUARIO'],
                            'CLAVE_USR'   => $_parametros['CLAVE_USR'],
                            'CMD_TRANS'   => $_parametros['CMD_TRANS'],
                            'ID_TERMINAL'   => $_parametros['ID_TERMINAL'],
                            'MONTO'   => $_parametros['MONTO'],
                            'MODO'   => $_parametros['MODO'],
                            'NUMERO_CONTROL'   => $_numero_control."CAVVVACIO",
                            'REF_CLIENTE1'   => $_parametros['REF_CLIENTE1'],
                            'NUMERO_TARJETA'   => $_parametros['NUMERO_TARJETA'],
                            'FECHA_EXP'   => $_parametros['FECHA_EXP'],
                            'CODIGO_SEGURIDAD'   => $_parametros['CODIGO_SEGURIDAD'],
                            'MODO_ENTRADA'   => $_parametros['MODO_ENTRADA'],
                            'IDIOMA_RESPUESTA'   => $_parametros['IDIOMA_RESPUESTA'],
                            'ESTATUS_3D' => $_3d_status,
                            'ECI' => $_3d_eci,
                            'XID' => $_3d_xid,
                            'VERSION_3D' => "2"
                            
                        );

                    } else {
                        $params = array (
                            'ID_AFILIACION'   => $_parametros['ID_AFILIACION'],
                            'USUARIO'   => $_parametros['USUARIO'],
                            'CLAVE_USR'   => $_parametros['CLAVE_USR'],
                            'CMD_TRANS'   => $_parametros['CMD_TRANS'],
                            'ID_TERMINAL'   => $_parametros['ID_TERMINAL'],
                            'MONTO'   => $_parametros['MONTO'],
                            'MODO'   => $_parametros['MODO'],
                            'NUMERO_CONTROL'   => $_numero_control."NONE",
                            'REF_CLIENTE1'   => $_parametros['REF_CLIENTE1'],
                            'NUMERO_TARJETA'   => $_parametros['NUMERO_TARJETA'],
                            'FECHA_EXP'   => $_parametros['FECHA_EXP'],
                            'CODIGO_SEGURIDAD'   => $_parametros['CODIGO_SEGURIDAD'],
                            'MODO_ENTRADA'   => $_parametros['MODO_ENTRADA'],
                            'IDIOMA_RESPUESTA'   => $_parametros['IDIOMA_RESPUESTA'],
                            'ESTATUS_3D' => $_3d_status,
                            'ECI' => $_3d_eci,
                            'XID' => $_3d_xid,
                            'CAVV' => $_3d_cavv,
                            'VERSION_3D' => "2"
                        );

                    }

                                       
                    try {
                        $client = $this->_httpClientFactory->create();
                        $client->setUri("https://via.banorte.com/payw2")
                            ->setParameterPost($params)
                            ->setMethod(\Zend_Http_Client::POST);
                        
                        $response = $client->request();
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


                   

                } catch (\Exception $e) {
                    $this->logger->error('#ERROR', array('message' => $e->getMessage(), 'code' => $e->getCode(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()));
                }

                
                
                

            }
            
            
        } else {
            return $this->resultRedirectFactory->create()->setPath('');
        }
        unset($_SESSION['banorte_parametros_3dsecure']);
        unset($_SESSION['banorte_parametros_payworks']);
        unset($_SESSION['banorte_respuesta_cybersource']);
        unset($_SESSION['banorte_redirect_url']);
        header('HTTP/1.1 200 OK');
        exit;   

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



    
    
    /**
     * encuentraTransaccion
     *
     * @param  mixed $magento_id
     * @return void
     */
    public function encuentraTransaccion($magento_id)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('sixplus1_banorte_transacciones');
        $sql = "SELECT * FROM " . $table . " WHERE magento_id = ". $magento_id;
        $transaccion_detalles = $connection->fetchRow($sql);
        $query = "DELETE FROM " . $table . " WHERE magento_id = ". $magento_id;
        $connection->query($query);
        return $transaccion_detalles;
    }

    
}

