<?php

namespace Restfull\Correios\Datasource;

use Restfull\Correios\Freight;

/**
 *
 */
interface FreightInterface
{
    /**
     * @param string $service
     *
     * @return array
     */
    public function payload($service): array;

    /**
     * @param string $zipCode
     *
     * @return $this
     */
    public function origin(string $zipCode): Freight;

    /**
     * @param string $zipCode
     *
     * @return $this
     */
    public function destination(string $zipCode): Freight;

    /**
     * @param array ...$services
     *
     * @return $this
     */
    public function services(array $services): Freight;

    /**
     * @param string $code
     * @param string $password
     *
     * @return $this
     */
    public function credentials(string $code, string $password): Freight;

    /**
     * @param int $format
     *
     * @return $this
     */
    public function package(int $format): Freight;

    /**
     * @param bool $useOwnHand
     *
     * @return $this
     */
    public function useOwnHand(bool $useOwnHand): Freight;

    /**
     * @param float $value
     *
     * @return $this
     */
    public function declaredValue(float $value): Freight;

    /**
     * @param float $width
     * @param float $height
     * @param float $length
     * @param float $weight
     * @param int $quantity
     *
     * @return $this
     */
    public function item(float $width, float $height, float $length, float $weight, int $quantity = 1): Freight;

    /**
     * @return array
     */
    public function calculate(): array;
}
