<?php

namespace Restfull\Correios\Datasource;

/**
 *
 */
interface ZipCodeInterface
{
    /**
     * @return array
     */
    public function find(): array;

    /**
     * @param string $zipcode
     *
     * @return mixed
     */
    public function zipCode(string $zipcode = null);
}
