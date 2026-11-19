<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use Exception;

/**
 * Reader for qqwry.ipdb (IPIP.net ipdb format)
 * Supports IPv4 & IPv6, UTF-8 encoded
 */
final class QQWryIpdb
{
    private const IPV4 = 1;
    private const IPV6 = 2;

    private $file = null;
    private int $fileSize = 0;
    private int $nodeCount = 0;
    private int $nodeOffset = 0;
    private array $meta = [];
    private int $v4offset = 0;

    private static ?self $instance = null;
    private static string $loadedPath = '';

    public function __construct(string $database)
    {
        if (! is_readable($database)) {
            throw new InvalidArgumentException("qqwry.ipdb not found or not readable: {$database}");
        }

        $this->file = @fopen($database, 'rb');
        if ($this->file === false) {
            throw new InvalidArgumentException("Cannot open: {$database}");
        }

        $this->fileSize = (int) filesize($database);
        $metaLength = unpack('N', fread($this->file, 4))[1];
        $text = fread($this->file, $metaLength);
        $this->meta = json_decode($text, true);

        if (! isset($this->meta['fields'], $this->meta['languages'])) {
            throw new Exception('qqwry.ipdb metadata error.');
        }

        $this->nodeCount = $this->meta['node_count'];
        $this->nodeOffset = 4 + $metaLength;
    }

    public static function getInstance(): ?self
    {
        $path = BASE_PATH . '/storage/qqwry.ipdb';
        if (! is_readable($path)) {
            return null;
        }
        if (self::$instance === null || self::$loadedPath !== $path) {
            try {
                self::$instance = new self($path);
                self::$loadedPath = $path;
            } catch (Exception) {
                return null;
            }
        }
        return self::$instance;
    }

    /**
     * @return string[] | null  [country_name, region_name, city_name, ...]
     */
    public function find(string $ip, string $language = 'CN'): ?array
    {
        if (! isset($this->meta['languages'][$language])) {
            $language = array_key_first($this->meta['languages']);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
            return null;
        }

        if (str_contains($ip, '.') && ! $this->supportV4()) {
            return null;
        }
        if (str_contains($ip, ':') && ! $this->supportV6()) {
            return null;
        }

        try {
            $node = $this->findNode($ip);
            if ($node > 0) {
                $data = $this->resolve($node);
                if ($data === null) {
                    return null;
                }
                $values = explode("\t", $data);
                return array_slice($values, $this->meta['languages'][$language], count($this->meta['fields']));
            }
        } catch (Exception) {
            return null;
        }

        return null;
    }

    public function findMap(string $ip, string $language = 'CN'): ?array
    {
        $arr = $this->find($ip, $language);
        if ($arr === null) {
            return null;
        }
        return array_combine($this->meta['fields'], $arr);
    }

    private function findNode(string $ip): int
    {
        $binary = inet_pton($ip);
        $bitCount = strlen($binary) * 8;
        $node = 0;

        if ($bitCount === 32) {
            if ($this->v4offset === 0) {
                for ($i = 0; $i < 96 && $node < $this->nodeCount; $i++) {
                    $idx = ($i >= 80) ? 1 : 0;
                    $node = $this->readNode($node, $idx);
                    if ($node > $this->nodeCount) {
                        return 0;
                    }
                }
                $this->v4offset = $node;
            } else {
                $node = $this->v4offset;
            }
        }

        for ($i = 0; $i < $bitCount; $i++) {
            if ($node >= $this->nodeCount) {
                break;
            }
            $node = $this->readNode($node, 1 & ((0xFF & ord($binary[$i >> 3])) >> 7 - ($i % 8)));
        }

        if ($node === $this->nodeCount) {
            return 0;
        }
        if ($node > $this->nodeCount) {
            return $node;
        }

        throw new Exception('find node failed.');
    }

    private function readNode(int $node, int $index): int
    {
        return unpack('N', $this->read($node * 8 + $index * 4, 4))[1];
    }

    private function resolve(int $node): ?string
    {
        $resolved = $node - $this->nodeCount + $this->nodeCount * 8;
        if ($resolved >= $this->fileSize) {
            return null;
        }
        $bytes = $this->read($resolved, 2);
        $size = unpack('N', str_pad($bytes, 4, "\x00", STR_PAD_LEFT))[1];
        return $this->read($resolved + 2, $size);
    }

    private function read(int $offset, int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        fseek($this->file, $offset + $this->nodeOffset);
        $value = fread($this->file, $length);
        if (strlen($value) !== $length) {
            throw new Exception('qqwry.ipdb read error.');
        }
        return $value;
    }

    public function supportV4(): bool
    {
        return ($this->meta['ip_version'] & self::IPV4) === self::IPV4;
    }

    public function supportV6(): bool
    {
        return ($this->meta['ip_version'] & self::IPV6) === self::IPV6;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function __destruct()
    {
        if (is_resource($this->file)) {
            fclose($this->file);
        }
    }
}
