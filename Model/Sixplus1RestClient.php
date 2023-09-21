<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Model;

class Sixplus1RestClient implements Sixplus1RestClientInterface {

    protected $_httpClientFactory;

    protected $logger;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\HTTP\LaminasClientFactory $httpClientFactory
    ){
        $this->logger = $logger;
        $this->_httpClientFactory = $httpClientFactory;
    }
    

    /**
     * sendRequest
     *
     * @param  mixed $params
     * @return void
     */
    public function sendRequest($params){
        try {
            $client = $this->_httpClientFactory->create();
            $client->setUri(self::BANORTE_PAYWORKS)
                ->setParameterPost($params)
                ->setMethod(\Laminas\Http\Request::METHOD_POST);
            
            $response = $client->request();
            return $response;

        } catch (\Exception $e) {
            $this->logger->debug($e);
            throw new \Magento\Framework\Validator\Exception(__($e));
        }
    }





}

