# Correios

## About Correios

An easy way to interact with the main features of Correios.

## Installation

* Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
* Run `php composer.phar require rest-full/correios` or composer installed globally `compser require rest-full/correios` or composer.json `"rest-full/correios": "1.0.0"` and install or update.

## Usage

Search for zip code the Correios.

``` php
<?php

require_once __DIR__ . '../vendor/autoload.php';

use Restfull\Correios\Client;

$correios = new Client();
echo $correios->zipcode('20520-054');
```

calculate the shipping of the order

``` php
<?php

require_once __DIR__ . '../vendor/autoload.php';

use Restfull\Correios\Client;

$correios = new Client;
$correios->multiZipCode(['21050-560', '21520-001'])->freight(['PAC'], [
    'width' => 16.4, 'height' => 15.9, 'length' => 17.8, 'weight' => 53.1,
    'quantity' => 1
]);
```

## Licença

The Correios is open-sourced software licensed under the[MIT license](https://opensource.org/licenses/MIT) .
