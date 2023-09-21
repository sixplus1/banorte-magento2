<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Api;


interface Sixplus1RestClientInterface {

    const BANORTE_PAYWORKS = "https://via.banorte.com/payw2";

        
    /**
     * Send Request to Banorte Service
     *
     * @param  array $params
     * @return void
     */
    public function sendRequest($params);


}

