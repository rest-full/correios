<?php

require_once __DIR__ . '../vendor/autoload.php';

use Restfull\Correios\Client;

$correios = new Client();
echo $correios->zipcode('20520-054');
