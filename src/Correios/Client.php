<?php

namespace Restfull\Correios;

use GuzzleHttp\Client as HttpClient;
use Restfull\Correios\Datasource\FreightInterface;
use Restfull\Correios\Datasource\ZipCodeInterface;
use Restfull\Error\Exceptions;

/**
 *
 */
class Client
{
    /**
     * @var FreightInterface
     */
    private $freight;

    /**
     * @var ZipCodeInterface
     */
    private $zipcode;

    /**
     * @var array
     */
    private $zipcodes;

    /**
     */
    public function __construct()
    {
        $this->http = new HttpClient;
        $this->freight = new Freight($this->http);
        $this->zipcode = new ZipCode($this->http);
        return $this;
    }

    /**
     * @param array $services
     * @param array $sizes
     *
     * @return array
     */
    public function freight(array $services, array $sizes): array
    {
        $keys = ['width', 'height', 'length', 'weight', 'quantity'];
        if ($this->validFreight($keys, $sizes)) {
            throw new Exceptions(
                'there is no width or height or length or weight or quantity in the sizes variable',
                404
            );
        }
        $freight = $this->freight->origin($this->zipcodes[0]->zipCode())->destination(
            $this->zipcodes[1]->zipCode()
        )->services($services);
        foreach ($sizes as $size) {
            $freight->item($size[$keys[0]], $size[$keys[1]], $size[$keys[2]], $size[$keys[3]], $size[$keys[4]]);
        }
        return $freight->calculate();
    }

    /**
     * @param array $keys
     * @param array $sizes
     *
     * @return bool
     */
    private function validFreight(array $keys, array $sizes): bool
    {
        $valids = [];
        if (count($sizes)) {
            foreach ($sizes as $size) {
                $valid = [];
                for ($a = 0; $a < count($keys); $a++) {
                    $valid[] = array_key_exists($keys[$a], $size) ? 'verdadeiro' : 'falso';
                }
                $valids[] = in_Array('falso', $valid) ? 'verdadeiro' : 'falso';
            }
        } else {
            $valid = [];
            for ($a = 0; $a < count($keys); $a++) {
                $valid[] = array_key_exists($keys[$a], $size) ? 'verdadeiro' : 'falso';
            }
            $valids[] = in_Array('falso', $valid) ? 'verdadeiro' : 'falso';
        }
        $count = 0;
        for ($a = 0; $a < count($valids); $a++) {
            if ($valids[$a] === 'falso') {
                $count++;
            }
        }
        return $count === 0;
    }

    /**
     * @param string $zipcode
     *
     * @return string
     */
    public function zipcode(string $zicode): array
    {
        return $this->zipcode->zipCode($zicode)->find();
    }

    /**
     * @param array $zipcodes
     *
     * @return array
     */
    public function multiZipCode(array $zipcodes): Client
    {
        foreach ($zipcodes as $zipcode) {
            $this->zipcodes[] = $this->zipcode->zipCode($zipcode);
        }
        return $this;
    }
}
