<?php

class TportApi
{

    public $baseUrl = 'https://api.tport.nl/rest/';
    public $timeout = 20;

    private $apiKey = '5E8SVNzr8sPqaxoP9ZN9xjeV6PwRKCOScEhyG7CdKzzuDjARFnT7RnV6mRIjvEvl';
    private $secretKey = 'EUqBu7ECBxzciFDYDNHuDHtkka2KlShVvzw9mJ3iJUJIrGCC-WQVN2V1FsvuS7yR';
    private $version;


    public function __construct($apiKey = null, $secretKey = null, $version = 1)
    {
        if(!is_null($apiKey)) {
            $this->apiKey = $apiKey;
        }
        if(!is_null($secretKey)) {
            $this->secretKey = $secretKey;
        }
        $this->version = $version;
    }

    public function query($method, $data = null, $httpType = 'GET', $signed = false)
    {
        $url = rtrim($this->baseUrl, '/') . '/v'.$this->version . '/' . trim($method, '/');
        $headers = ['Content-Type' => 'application/json'];


        if($signed) {
            $timestamp = (time() * 1000);
            $data['timestamp'] = $timestamp;
        }
        $query = is_array($data) ? json_encode($data) : $data;

        if ($signed) {
            $headers['X-TPORT-APIKEY'] = $this->apiKey;
            $headers['Signature'] = $this->createSignature($query);
        }

        $opt = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FILETIME       => true,
            CURLOPT_CUSTOMREQUEST  => $httpType,
        ];
        if ($query) {
            $opt += [
                CURLOPT_POSTFIELDS => $query
            ];
        }

        $curlheaders = [];
        foreach ($headers as $name => $value) {
            $curlheaders[] = $name . ': ' . $value;
        }
        $opt += [
            CURLOPT_HTTPHEADER => $curlheaders,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $opt);
        $r = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $ret = json_decode($r, true);

        if ($ret === false || $info['http_code'] != 200) {
            throw new \Exception(__METHOD__ . ':' . $url . ' ' . $query . PHP_EOL . $r);
        }

        return $ret;
    }

    public function createSignature($query)
    {
        $hash = hash_hmac('sha256', $query, $this->secretKey, false);

        return $hash;
    }

}
