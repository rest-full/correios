<?php

require_once __DIR__ . '../vendor/autoload.php';

use Restfull\Correios\Client;

$correios = new Client;
$correios->multiZipCode(['21050-560', '21520-001'])->freight(['PAC'],
    ['width' => 16.4, 'height' => 15.9, 'length' => 17.8, 'weight' => 53.1, 'quantity' => 1]);
