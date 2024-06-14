<?php

namespace App;

use Exception;

class CoinMarketAPI implements ApiClient
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @throws Exception
     */
    public function getList(int $start, int $limit, string $convert): array
    {
        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest';
        $parameters = [
            'start' => $start,
            'limit' => $limit,
            'convert' => $convert
        ];
        return $this->makeRequest($url, $parameters);
    }

    /**
     * @throws Exception
     */
    public function getSymbol(string $symbol, string $convert): array
    {
        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest';
        $parameters = [
            'symbol' => $symbol,
            'convert' => $convert
        ];
        return $this->makeRequest($url, $parameters);
    }

    /**
     * @throws Exception
     */
    private function makeRequest(string $url, array $parameters): array
    {
        $headers = [
            'Accepts: application/json',
            'X-CMC_PRO_API_KEY: ' . $this->apiKey
        ];
        $qs = http_build_query($parameters);
        $request = "{$url}?{$qs}";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $request,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => 1
        ));
        $response = curl_exec($curl);
        if ($response === false) {
            curl_close($curl);
            throw new Exception("Curl error: " . curl_error($curl));
        }
        curl_close($curl);
        return json_decode($response, true);
    }
}