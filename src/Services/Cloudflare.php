<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Exception;

final class Cloudflare
{
    public static function initR2(): S3Client
    {
        $credentials = new Credentials(Config::obtain('r2_access_key_id'), Config::obtain('r2_access_key_secret'));

        $options = [
            'region' => 'auto',
            'endpoint' => 'https://' . Config::obtain('r2_account_id') . '.r2.cloudflarestorage.com',
            'version' => 'latest',
            'credentials' => $credentials,
        ];

        return new S3Client($options);
    }

    public static function uploadR2($name, $file): void
    {
        $r2 = self::initR2();

        try {
            $r2->upload(
                Config::obtain('r2_bucket_name'),
                $name,
                $file,
            );
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public static function genR2PresignedUrl($fileName): string
    {
        $r2 = self::initR2();

        $cmd = $r2->getCommand('GetObject', [
            'Bucket' => Config::obtain('r2_bucket_name'),
            'Key' => $fileName,
        ]);

        return (string) $r2->createPresignedRequest(
            $cmd,
            '+' . Config::obtain('r2_client_download_timeout') . ' minutes'
        )->getUri();
    }
}
