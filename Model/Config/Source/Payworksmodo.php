<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sixplus1\Banorte\Model\Config\Source;


/**
 * @api
 * @since 100.0.2
 */
class Payworksmodo implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => "PRD", 'label' => __('Producción')], 
            ['value' => "AUT", 'label' => __('Prueba Autorizada')],
            ['value' => "DEC", 'label' => __('Prueba Declinado')],
            ['value' => "RND", 'label' => __('Prueba Aleatorio')]
            ];
    }


   
}
