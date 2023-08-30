<?php

namespace Restfull\Correios;

use Correios\WebService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Restfull\Correios\Datasource\ZipCodeInterface;

/**
 *
 */
class ZipCode implements ZipCodeInterface
{

    /**
     * @var ClientInterface
     */
    protected $http;

    /**
     * @var string
     */
    protected $zipcode;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $parsedXML;

    /**
     * @param ClientInterface $http
     */
    public function __construct(ClientInterface $http)
    {
        $this->http = $http;
        return $this;
    }

    /**
     * @return array
     */
    public function find(): array
    {
        $this->buildXMLBody()->sendWebServiceRequest()->parseXMLFromResponse();
        if ($this->hasErrorMessage()) {
            return $this->fetchErrorMessage();
        }
        return $this->fetchAddress();
    }

    /**
     * @return $this
     */
    protected function parseXMLFromResponse(): ZipCode
    {
        $parse = simplexml_load_string(
            str_replace(['soap:', 'ns2:',], null, $this->response->getBody()->getContents())
        );
        $this->parsedXML = json_decode(json_encode($parse->Body), true);
        return $this;
    }

    /**
     * @return $this
     */
    protected function sendWebServiceRequest(): ZipCode
    {
        $this->response = $this->http->post(
            'https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente',
            [
                'http_errors' => false,
                'body' => $this->body,
                'headers' => ['Content-Type' => 'application/xml; charset=utf-8', 'cache-control' => 'no-cache',],
            ]
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function buildXMLBody(): ZipCode
    {
        $this->body = trim(
            '<?xml version="1.0"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cli="http://cliente.bean.master.sigep.bsb.correios.com.br/"><soapenv:Header/><soapenv:Body><cli:consultaCEP><cep>' . $this->zipcode . '</cep></cli:consultaCEP></soapenv:Body></soapenv:Envelope>'
        );
        return $this;
    }

    /**
     * @return bool
     */
    protected function hasErrorMessage(): bool
    {
        return array_key_exists('Fault', $this->parsedXML);
    }

    /**
     * @return array
     */
    protected function fetchErrorMessage(): array
    {
        return ['error' => $this->messages($this->parsedXML['Fault']['faultstring']),];
    }

    /**
     * @param string $faultString
     *
     * @return string
     */
    protected function messages(string $faultString): string
    {
        return ['CEP INVÁLIDO' => 'CEP não encontrado',][$faultString];
    }

    /**
     * @return array
     */
    protected function fetchAddress(): array
    {
        $address = $this->parsedXML['consultaCEPResponse']['return'];
        $zipcode = preg_replace('/^([0-9]{5})([0-9]{3})$/', '${1}-${2}', $address['cep']);
        $complement = $this->getComplement($address);
        return [
            'zipcode' => $zipcode,
            'street' => $address['end'],
            'complement' => $complement,
            'district' => $address['bairro'],
            'city' => $address['cidade'],
            'uf' => $address['uf'],
        ];
    }

    /**
     * @param array $address
     *
     * @return array
     */
    protected function getComplement(array $address): array
    {
        $complement = [];
        if (array_key_exists('complemento', $address)) {
            $complement[] = $address['complemento'];
        }
        if (array_key_exists('complemento2', $address)) {
            $complement[] = $address['complemento2'];
        }
        return $complement;
    }

    /**
     * @param string|null $zipcode
     *
     * @return mixed
     */
    public function zipCode(string $zipcode = null)
    {
        if (is_null($zipcode)) {
            return $this->zipcode;
        }
        $this->zipcode = preg_replace('/[^0-9]/', null, $zipcode);
        return $this;
    }
}
