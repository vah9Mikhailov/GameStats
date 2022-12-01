<?php

namespace App\Api;

class Transport
{

    /**
     * @var string
     */
    private $host;

    /**
     * @param string $host
     */
    public function __construct(string $host)
    {
        $this->host = $host;
    }


    /**
     * @param string|null $params
     * @param string $header
     * @return string
     */
    public function getContents(?string $params, string $header = ''): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->host . "?" . $params ?? '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if (!empty($header)) {
            curl_setopt($ch,CURLOPT_HEADER, $header);
        }

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}
