<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;

class CodigoautpaywColumn extends Column
{
    protected $_orderRepository;
    protected $_searchCriteria;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteria,
        array $components = [],
        array $data = []
    )
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria = $criteria;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    
    /**
     * prepareDataSource
     *
     * @param  mixed $dataSource
     * @return void
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as $k => $item) {
                if($item['entity_id'] && $item['entity_id'] > 0){
                    $order = $this->_orderRepository->get($item['entity_id']);
                    $item[$this->getData('name')] = $order->getCodigoAutPayw();
                    $dataSource['data']['items'][$k] = $item;
                }
            }
        }

        return $dataSource;
    }
}