<?php

namespace OCA\Signer\Handler;

use OCA\Signer\Exception\SignerException;

class CfsslServerHandler
{
    const CSR_FILE = 'csr_server.json';
    const CONFIG_FILE = 'config_server.json';
    const TMP_DIR = '/tmp/cfssl/';

    public function createConfigServer(
        $commonName,
        $country,
        $organization,
        $organizationUnit,
        $key
    ) {
        $this->putCsrServer(
            $commonName,
            $country,
            $organization,
            $organizationUnit
        );
        $this->putConfigServer($key);
    }

    private function putCsrServer(
        $commonName,
        $country,
        $organization,
        $organizationUnit
    )
    {
        $filename = self::TMP_DIR.self::CSR_FILE;
        $content = [
            'CN' => $commonName,
            'key' => [
                'algo' => 'rsa',
                'size' => 2048,
            ],
            'names' => [
                [
                    'C' => $country,
                    'O' => $organization,
                    'OU' => $organizationUnit,
                    'CN' => $commonName,
                ],
            ],
        ];
        
        $response = file_put_contents($filename, json_encode($content));
        if ($response === false) {
            throw new SignerException("Error while writing CSR server file!", 500);
        }
    }

    private function putConfigServer(string $key)
    {
        $filename = self::TMP_DIR.self::CONFIG_FILE;
        $content = [
            'signing' => [
                'profiles' => [
                    'CA' => [
                        'auth_key' => 'key1',
                        'expiry' => '8760h',
                        'usages' => [
                            "signing",
                            "digital signature",
                            "cert sign"
                        ],
                    ],
                ],
            ],
            'auth_keys' => [
                'key1' => [
                    'key' => $key,
                    'type' => 'standard',
                ],
            ],
        ];

        $response = file_put_contents($filename, json_encode($content));
        if ($response === false) {
            throw new SignerException("Error while writing config server file!", 500);
        }
    }
}