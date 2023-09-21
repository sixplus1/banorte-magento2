<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Model\Payment;

use Magento\Framework\App\ObjectManager;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\Order\Invoice;
use Magento\Payment\Model\Method\Logger;

use Magento\Framework\App\ResourceConnection;


class Banorte extends \Magento\Payment\Model\Method\Cc
{

    const RESPUESTA_CODIGO_APROVADA = 'A';
    const CODE = 'sixplus1_banorte';
    protected $_code = self::CODE;
    

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;


    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $psrLogger;

    protected $Sixplus1RestClientFactory;

    protected $_invoiceService;

    protected $logger;

    protected $_checkoutSessionFactory;

    protected $resourceConnection;

    protected $encryptor;

   
    public function __construct(
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Psr\Log\LoggerInterface $logger_interface,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        Sixplus1RestClientInterfaceFactory $Sixplus1RestClientFactory
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate
        );

        $this->orderFactory = $orderFactory;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->quoteRepository = $quoteRepository;
        $this->orderSender = $orderSender;
        $this->_code = static::CODE;
        $this->storeManager = $storeManager;
        $this->_invoiceService = $invoiceService;
        $this->logger = $logger_interface;
        $this->_checkoutSessionFactory = $checkoutSessionFactory->create();
        $this->resourceConnection = $resourceConnection;
        $this->encryptor = $encryptor;
        $this->Sixplus1RestClientFactory = $Sixplus1RestClientFactory;
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
     * isAvailable
     *
     * @return void
     */
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }

    
    /**
     * capture
     *
     * @param  mixed $payment
     * @param  mixed $amount
     * @return void
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount){
       
        $order = $payment->getOrder();                
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Cantidad invalida para capturar.'));
        }               
        $payment->setAmount($amount);
        if(!$payment->getLastTransId()){            
            $this->processCapture($payment, $amount);
        } 
        
        return $this;

    }

    
    /**
     * authorize
     *
     * @param  mixed $payment
     * @param  mixed $amount
     * @return void
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount){
     
        $order = $payment->getOrder();                  
        $payment->setIsTransactionClosed(false);
        $payment->setSkipOrderProcessing(true);
        $this->processCapture($payment, $amount);
        return $this;
    }



    
    /**
     * refund
     *
     * @param  mixed $payment
     * @param  mixed $amount
     * @return void
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount){
        
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for refund.'));
        }
        
        try {
            $params_payworks = array (
                'ID_AFILIACION'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_id_afiliacion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'USUARIO'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_usuario', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'CLAVE_USR'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_usuario_clave', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'CMD_TRANS'   => 'DEVOLUCION',
                'ID_TERMINAL'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_id_terminal', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'MONTO'   => $amount,
                'MODO'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_modo', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'REFERENCIA'   => $order->getReferenciaPayw(),
                'IDIOMA_RESPUESTA'   => 'ES',
            );
            
            $client = $this->Sixplus1RestClientFactory->create();
            $response = $client->sendRequest($params);
            $responseBody = $response->getBody();
            $headers = $response->getHeaders();
         
    
            
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }        
        
        return $this;
    }



     /**     
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function processCapture(\Magento\Payment\Model\InfoInterface $payment, $amount) {

        $base_url = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        
        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();
        $ccNumber = $payment->getCcNumber();
        $ccType = $payment->getCcType();
        $ccExpMonth = $payment->getCcExpMonth();
        $ccExpYear = $payment->getCcExpYear();
        $ccCid = $payment->getCcCid();
        $_monto_pedido = (float) $amount;
		$_anioExpira = substr($ccExpYear, 2);
		$_mesExpira=sprintf("%02s",$ccExpMonth);
		$fechaExpiracion=$_mesExpira.$_anioExpira;
        $fechaExpiracion_3D = $_mesExpira."/".$_anioExpira;
        $modo_cybersource = $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_cybersource_modo', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


        //Flag solo 3D Secure para version Open Source
        $solo3d_sincybersource = true;
        
        if ($solo3d_sincybersource == true) {
    
            $params_3D = array (
                'CARD_NUMBER'   => $ccNumber,
                'CARD_EXP'   => $fechaExpiracion_3D,
                'AMOUNT'   => $_monto_pedido,
                'CARD_TYPE'   => $this->_convierteTipoTarjeta3D($ccType),
                'MERCHANT_ID'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_id_afiliacion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'MERCHANT_NAME'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_nombre_comercio', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'MERCHANT_CITY'   => $this->scopeConfig->getValue('payment/sixplus1_banorte/payworks_ciudad_comercio', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'FORWARD_PATH'   => $base_url.'sixplus1_banorte/payment/payment',
                '3D_CERTIFICATION'   => "03",
                'REFERENCE3D'   => $order->getIncrementId(),
                'CITY'   => $this->_limpiaCaracteres($billing->getCity()),
                'COUNTRY'   => "MX",
                'EMAIL'   => $order->getCustomerEmail(),
                'NAME'   => $this->_limpiaCaracteres($billing->getFirstname()),
                'LAST_NAME'   => $this->_limpiaCaracteres($billing->getLastname()),
                'POSTAL_CODE'   => $billing->getPostcode(),
                'STATE'   => $this->_convierteEstado($billing->getRegion()),
                'STREET'   => $this->_limpiaCaracteres(mb_strimwidth($billing->getStreetLine(1), 0, 49, "")),
                'THREED_VERSION'   => "2",
                'MOBILE_PHONE'   => str_replace(' ', '', $billing->getTelephone()),
                'CREDIT_TYPE'   => "CR"


            );
                            
            //Se guardan momentaneamente para luego del regreso ser destruidos
            $ccNumber_enc = $this->encryptor->encrypt($ccNumber);
            $ccCid_enc = $this->encryptor->encrypt($ccCid);
            $this->guardaTransaccion($order->getIncrementId(), $ccNumber_enc, $ccCid_enc);  
            $_SESSION['banorte_parametros_3dsecure'] = $params_3D;
            $_SESSION['banorte_redirect_url'] = $base_url.'sixplus1_banorte/payment/redirect';
            $_3dsecure = true;
            $_SESSION['banorte_redireccion_3dsecure'] = 1;
            return $this;



        }else{

            // versión Pro

        }        
    }




    
    /**
     * guardaTransaccion
     *
     * @param  mixed $magento_id
     * @param  mixed $transaccion
     * @param  mixed $transaccion_caduca
     * @return void
     */
    public function guardaTransaccion($magento_id, $transaccion, $transaccion_caduca)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('sixplus1_banorte_transacciones');
        $tableColumn = ['transaccion_id', 'magento_id', 'transaccion', 'transaccion_caduca'];
        $tableData[] = [null, $magento_id, $transaccion, $transaccion_caduca];
        $connection->insertArray($table, $tableColumn, $tableData);
    }



    /**
     * @param $estado
     * @return null|string
     * Convierte la cadena del Estado e el Codigo necesario para Payworks
     */
    protected function _convierteEstado($estado){

        switch ($estado) {
            case 'Aguascalientes':
                return 'AG';
                break;
            case 'Baja California':
                return 'BC';
                break;
            case 'Baja California Sur':
                return 'BS';
                break;
            case 'Campeche':
                return 'CM';
                break;
            case 'Chiapas':
                return 'CS';
                break;
            case 'Chihuahua':
                return 'CH';
                break;
            case 'Coahuila':
                return 'CO';
                break;
            case 'Colima':
                return 'CL';
                break;
            case 'Distrito Federal':
                return 'CX';
                break;
            case 'Ciudad de MÃ©xico':
                return 'CX';
                break;
            case 'Ciudad de México':
                return 'CX';
                break;
            case 'Durango':
                return 'DG';
                break;
            case 'Guanajuato':
                return 'GT';
                break;
            case 'Guerrero':
                return 'GR';
                break;
            case 'Hidalgo':
                return 'HG';
                break;
            case 'Jalisco':
                return 'JC';
                break;
            case 'Mexico':
                return 'EM';
                break;
            case 'Estado de México':
                return 'EM';
                break;
            case 'Michoacán':
                return 'MI';
                break;
            case 'Morelos':
                return 'MO';
                break;
            case 'Nayarit':
                return 'NA';
                break;
            case 'Nuevo Leon':
                return 'NL';
                break;
            case 'Oaxaca':
                return 'OA';
                break;
            case 'Puebla':
                return 'PU';
                break;
            case 'Querétaro':
                return 'QT';
                break;
            case 'Quintana Roo':
                return 'QR';
                break;
            case 'San Luis Potosi':
                return 'SL';
                break;
            case 'Sinaloa':
                return 'SI';
                break;
            case 'Sonora':
                return 'SO';
                break;
            case 'Tabasco':
                return 'TB';
                break;
            case 'Tamaulipas':
                return 'TM';
                break;
            case 'Tlaxcala':
                return 'TL';
                break;
            case 'Veracruz':
                return 'VE';
                break;
            case 'Yucatan':
                return 'YU';
                break;
            case 'Zacatecas':
                return 'ZA';
                break;
            
            default:
                return null;
                break;
        }

    }


    /**
     * @param $tipo
     * @return null|string
     * Convierte el tipo de tarjeta al de Cybersource
     */
    protected function _convierteTipoTarjeta($tipo){

        switch ($tipo) {
            case 'MC':
            return '002';
                break;
            case 'VI':
            return '001';
                break;
            
            default:
                return null;
                break;
        }

    }




    /**
     * @param $tipo
     * @return null|string
     * Convierte el tipo de tarjeta al de 3D Secure
     */
    protected function _convierteTipoTarjeta3D($tipo){
        switch ($tipo) {
            case 'MC':
            return 'MC';
                break;
            case 'VI':
            return 'VISA';
                break;
            
            default:
                return null;
                break;
        }
    }



    /**
     * @param $string
     * @return mixed
     * Limpia un string dado en caracteres especiales
     */
    protected function _limpiaCaracteres($string){
        $string = str_replace(
            array('Ã¡', 'Ã ', 'Ã¤', 'Ã¢', 'Âª', 'Ã', 'Ã€', 'Ã‚', 'Ã„'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $string
        );
        $string = str_replace(
            array('Ã©', 'Ã¨', 'Ã«', 'Ãª', 'Ã‰', 'Ãˆ', 'ÃŠ', 'Ã‹'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $string
        );
        $string = str_replace(
            array('Ã­', 'Ã¬', 'Ã¯', 'Ã®', 'Ã', 'ÃŒ', 'Ã', 'ÃŽ'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $string
        );
        $string = str_replace(
            array('Ã³', 'Ã²', 'Ã¶', 'Ã´', 'Ã“', 'Ã’', 'Ã–', 'Ã”'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $string
        );
        $string = str_replace(
            array('Ãº', 'Ã¹', 'Ã¼', 'Ã»', 'Ãš', 'Ã™', 'Ã›', 'Ãœ'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $string
        );
        $string = str_replace(
            array('Ã±', 'Ã‘', 'Ã§', 'Ã‡'),
            array('n', 'N', 'c', 'C',),
            $string
        );
        return $string;
    }



    /**
     * @param Address $billing
     * @return boolean
     */
    public function validateAddress($billing) {
        if ($billing->getStreetLine(1) && $billing->getCity() && $billing->getPostcode() && $billing->getRegion() && $billing->getCountryId()) {
            return true;
        }
        return false;
    }


    /**
     * Set the payment action to authorize_and_capture
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return self::ACTION_AUTHORIZE; 
    }


    
    /**
     * createInvoice
     *
     * @param  mixed $order
     * @param  mixed $codigo_autorizacion
     * @param  mixed $monto
     * @return void
     */
    public function createInvoice($order,$codigo_autorizacion, $monto)
    {
        if ($order->canInvoice()) {

            $requiresInvoice = true;
            /** @var InvoiceCollection $invoiceCollection */
            $invoiceCollection = $order->getInvoiceCollection();
            if ( $invoiceCollection->count() > 0 ) {
                /** @var Invoice $invoice */
                foreach ($invoiceCollection as $invoice ) {
                    if ( $invoice->getState() == Invoice::STATE_OPEN) {
                        $invoice->setState(Invoice::STATE_PAID);
                        $invoice->setTransactionId($codigo_autorizacion);
                        $invoice->pay()->save();
                        $requiresInvoice = false;
                        break;
                    }
                }
            }
            
            if ( $requiresInvoice ) {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->setTransactionId($codigo_autorizacion);
                $invoice->pay()->save();
            }
            $payment = $order->getPayment();                                
            $payment->setAmountPaid($monto);
            $payment->setIsTransactionPending(false);
            $payment->save();

            return true;

        }
        return false;
    }




    /**
     * @param Exception $e
     * @return string
     */
    public function error($e, $mensaje) {
        return 'ERROR. '.$mensaje;
    }

}

