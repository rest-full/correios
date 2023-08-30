<?php

namespace Restfull\Correios;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Restfull\Correios\Datasource\FreightInterface;

/**
 *
 */
class Freight implements FreightInterface
{
    /**
     * @var array
     */
    protected $services = [];

    /**
     * @var array
     */
    protected $defaultPayload = [
        'nCdEmpresa' => '',
        'sDsSenha' => '',
        'nCdServico' => '',
        'sCepOrigem' => '',
        'sCepDestino' => '',
        'nCdFormato' => 1,
        'nVlLargura' => 0,
        'nVlAltura' => 0,
        'nVlPeso' => 0,
        'nVlComprimento' => 0,
        'nVlDiametro' => 0,
        'sCdMaoPropria' => 'N',
        'nVlValorDeclarado' => 0,
        'sCdAvisoRecebimento' => 'N',
    ];

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var ClientInterface
     */
    protected $http;

    /**
     * @param ClientInterface $http
     */
    public function __construct(ClientInterface $http)
    {
        $this->http = $http;
        return $this;
    }

    /**
     * @param string $zipCode
     *
     * @return $this
     */
    public function origin(string $zipCode): Freight
    {
        $this->payload['sCepOrigem'] = preg_replace('/[^0-9]/', null, $zipCode);
        return $this;
    }

    /**
     * @param string $zipCode
     *
     * @return $this
     */
    public function destination(string $zipCode): Freight
    {
        $this->payload['sCepDestino'] = preg_replace('/[^0-9]/', null, $zipCode);
        return $this;
    }

    /**
     * @param array $services
     *
     * @return $this
     */
    public function services(array $services): Freight
    {
        $this->services = count($this->services) > 0 ? array_merge(
            $this->services,
            array_unique($services)
        ) : array_unique($services);
        return $this;
    }

    /**
     *
     * @param string $code
     * @param string $password
     *
     * @return $this
     */
    public function credentials(string $code, string $password): Freight
    {
        $this->payload['nCdEmpresa'] = $code;
        $this->payload['sDsSenha'] = $password;
        return $this;
    }

    /**
     * @param int $format
     *
     * @return $this
     */
    public function package(int $format): Freight
    {
        $this->payload['nCdFormato'] = $format;
        return $this;
    }

    /**
     * @param bool $useOwnHand
     *
     * @return $this
     */
    public function useOwnHand(bool $useOwnHand): Freight
    {
        $this->payload['sCdMaoPropria'] = (bool)$useOwnHand ? 'S' : 'N';
        return $this;
    }

    /**
     * @param float $value
     *
     * @return $this
     */
    public function declaredValue(float $value): Freight
    {
        $this->payload['nVlValorDeclarado'] = $value;
        return $this;
    }

    /**
     * @param float $width
     * @param float $height
     * @param float $length
     * @param float $weight
     * @param int $quantity
     *
     * @return $this
     */
    public function item(float $width, float $height, float $length, float $weight, int $quantity = 1): Freight
    {
        $this->items[] = compact('width', 'height', 'length', 'weight', 'quantity');
        return $this;
    }

    /**
     * @return array
     */
    public function calculate(): array
    {
        $servicesResponses = array_map(function ($service) {
            return $this->http->get(
                'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo',
                ['query' => $this->payload($service)]
            );
        }, $this->services);
        $services = array_map([$this, 'fetchCorreiosService'], $servicesResponses);
        return array_map([$this, 'transformCorreiosService'], $services);
    }

    /**
     * @param string $service
     *
     * @return array
     */
    public function payload($service): array
    {
        $this->payload['nCdServico'] = $service;
        if ($this->items) {
            $this->payload['nVlLargura'] = $this->width();
            $this->payload['nVlAltura'] = $this->height();
            $this->payload['nVlComprimento'] = $this->length();
            $this->payload['nVlDiametro'] = 0;
            $this->payload['nVlPeso'] = $this->useWeightOrVolume();
        }
        return array_merge($this->defaultPayload, $this->payload);
    }

    /**
     * @return int|float
     */
    protected function width()
    {
        return max(
            array_map(static function ($item) {
                return $item['width'];
            }, $this->items)
        );
    }

    /**
     * @return int|float
     */
    protected function height()
    {
        return array_sum(
            array_map(static function ($item) {
                return $item['height'] * $item['quantity'];
            }, $this->items)
        );
    }

    /**
     * @return int|float
     */
    protected function length()
    {
        return max(
            array_map(static function ($item) {
                return $item['length'];
            }, $this->items)
        );
    }

    /**
     * @return int|float
     */
    protected function useWeightOrVolume()
    {
        if ($this->volume() < 10 || $this->volume() <= $this->weight()) {
            return $this->weight();
        }
        return $this->volume();
    }

    /**
     * @return int|float
     */
    protected function volume()
    {
        return ($this->length() * $this->width() * $this->height()) / 6000;
    }

    /**
     * @return int|float
     */
    protected function weight()
    {
        return array_sum(
            array_map(static function ($item) {
                return $item['weight'] * $item['quantity'];
            }, $this->items)
        );
    }

    /**
     * @param Response $response
     *
     * @return array
     */
    protected function fetchCorreiosService(Response $response): array
    {
        $xml = simplexml_load_string($response->getBody()->getContents());
        $result = json_decode(json_encode($xml->Servicos), false);
        return get_object_vars($result->cServico);
    }

    /**
     * @param array $service
     *
     * @return array
     */
    protected function transformCorreiosService(array $service): array
    {
        $error = [];
        if ($service['Erro'] != 0) {
            $error = ['code' => $service['Erro'], 'message' => $service['MsgErro'],];
        }
        return [
            'name' => $this->friendlyServiceName($service['Codigo']),
            'code' => $service['Codigo'],
            'price' => (float)str_replace(',', '.', $service['Valor']),
            'deadline' => (int)$service['PrazoEntrega'],
            'error' => $error,
        ];
    }

    /**
     * @param string $code
     *
     * @return string|null
     */
    protected function friendlyServiceName(string $code)
    {
        $services = [
            'PAC' => 'PAC',
            'SEDEX' => 'Sedex',
            'SEDEX_A_COBRAR' => 'Sedex a Cobrar',
            'SEDEX_10' => 'Sedex 10',
            'SEDEX_HOJE' => 'Sedex Hoje'
        ];
        switch ($code) {
            case "PAC_CONTRATO":
            case "PAC_CONTRATO_04812":
            case "PAC_CONTRATO_41068":
            case "PAC_CONTRATO_41211":
                return 'PAC';
            case "SEDEX_CONTRATO":
            case "SEDEX_CONTRATO_04316":
            case "SEDEX_CONTRATO_40096":
            case "SEDEX_CONTRATO_40436":
            case "SEDEX_CONTRATO_40444":
            case "SEDEX_CONTRATO_40568":
                return 'Sedex';
            default:
                return array_key_exists($code, $services) ? $services[$code] : null;
        }
    }
}
