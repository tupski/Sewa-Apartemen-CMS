<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaController;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * SEC-08 regression coverage for the media URL-import SSRF guards.
 *
 * Two properties are asserted:
 *  1. Non-routable / internal address ranges are refused — including the cloud
 *     metadata endpoint, CGNAT, and the IPv6 equivalents.
 *  2. The validated IP is pinned via CURLOPT_RESOLVE so curl cannot perform its
 *     own DNS lookup and land somewhere else (DNS rebinding).
 */
class MediaUrlImportSsrfTest extends TestCase
{
    private MediaControllerSsrfProbe $probe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->probe = new MediaControllerSsrfProbe();
    }

    #[DataProvider('blockedAddresses')]
    public function test_blocked_addresses_are_rejected(string $ip): void
    {
        $this->assertTrue($this->probe->probeIsBlocked($ip), $ip . ' should be blocked');
    }

    /** @return array<string, array{0: string}> */
    public static function blockedAddresses(): array
    {
        return [
            'cloud metadata'          => ['169.254.169.254'],
            'link-local low'          => ['169.254.0.1'],
            'link-local high'         => ['169.254.255.254'],
            'cgnat low'               => ['100.64.0.1'],
            'cgnat mid'               => ['100.100.50.20'],
            'cgnat high'              => ['100.127.255.254'],
            'rfc1918 10/8'            => ['10.1.2.3'],
            'rfc1918 172.16/12'       => ['172.16.30.40'],
            'rfc1918 192.168/16'      => ['192.168.1.1'],
            'loopback'                => ['127.0.0.1'],
            'loopback alt'            => ['127.1.2.3'],
            'this network'            => ['0.0.0.0'],
            'this network host'       => ['0.1.2.3'],
            'multicast'               => ['224.0.0.1'],
            'broadcast'               => ['255.255.255.255'],
            'ipv6 loopback'           => ['::1'],
            'ipv6 unspecified'        => ['::'],
            'ipv6 link-local'         => ['fe80::1'],
            'ipv6 unique-local'       => ['fd00::1'],
            'ipv6 mapped metadata'    => ['::ffff:169.254.169.254'],
            'ipv6 mapped rfc1918'     => ['::ffff:192.168.0.1'],
            'not an address'          => ['not-an-ip'],
        ];
    }

    #[DataProvider('allowedAddresses')]
    public function test_public_addresses_are_allowed(string $ip): void
    {
        $this->assertFalse($this->probe->probeIsBlocked($ip), $ip . ' should be allowed');
    }

    /** @return array<string, array{0: string}> */
    public static function allowedAddresses(): array
    {
        return [
            'google dns'   => ['8.8.8.8'],
            'cloudflare'   => ['1.1.1.1'],
            'public v4'    => ['93.184.216.34'],
            'just below cgnat' => ['100.63.255.255'],
            'just above cgnat' => ['100.128.0.1'],
            'public v6'    => ['2606:4700:4700::1111'],
        ];
    }

    public function test_literal_ip_host_short_circuits_to_that_ip(): void
    {
        $this->assertSame('8.8.8.8', $this->probe->probeAssertPublicHost('8.8.8.8'));
    }

    public function test_literal_private_ip_host_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->probe->probeAssertPublicHost('169.254.169.254');
    }

    public function test_curl_options_pin_the_validated_ip(): void
    {
        $options = $this->probe->probeCurlOptions(
            'https://cdn.example.com/photo.jpg',
            'cdn.example.com',
            443,
            '93.184.216.34',
            1024
        );

        $this->assertArrayHasKey(CURLOPT_RESOLVE, $options);
        $this->assertSame(['cdn.example.com:443:93.184.216.34'], $options[CURLOPT_RESOLVE]);
    }

    public function test_curl_options_bracket_ipv6_pinned_address(): void
    {
        $options = $this->probe->probeCurlOptions(
            'https://cdn.example.com/photo.jpg',
            'cdn.example.com',
            443,
            '2606:4700:4700::1111',
            1024
        );

        $this->assertSame(
            ['cdn.example.com:443:[2606:4700:4700::1111]'],
            $options[CURLOPT_RESOLVE]
        );
    }

    public function test_curl_options_omit_resolve_for_literal_ip_hosts(): void
    {
        $options = $this->probe->probeCurlOptions(
            'https://93.184.216.34/photo.jpg',
            '93.184.216.34',
            443,
            '93.184.216.34',
            1024
        );

        $this->assertArrayNotHasKey(CURLOPT_RESOLVE, $options);
    }

    public function test_existing_ssrf_guards_are_preserved(): void
    {
        $options = $this->probe->probeCurlOptions(
            'https://cdn.example.com/photo.jpg',
            'cdn.example.com',
            443,
            '93.184.216.34',
            4096
        );

        $this->assertFalse($options[CURLOPT_FOLLOWLOCATION], 'redirects must stay disabled');
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame(4096, $options[CURLOPT_MAXFILESIZE]);
    }

    public function test_import_of_metadata_endpoint_url_fails_closed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->probe->probeDownload('http://169.254.169.254/latest/meta-data/');
    }

    public function test_import_of_rfc1918_url_fails_closed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->probe->probeDownload('http://192.168.1.1/x.jpg');
    }

    public function test_import_of_cgnat_url_fails_closed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->probe->probeDownload('http://100.64.12.34/x.jpg');
    }

    public function test_import_of_bracketed_ipv6_loopback_fails_closed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->probe->probeDownload('http://[::1]/x.jpg');
    }
}

/**
 * Exposes MediaController's protected SSRF helpers for assertion.
 */
class MediaControllerSsrfProbe extends MediaController
{
    public function __construct()
    {
        // Intentionally skip parent::__construct() — it registers the `auth`
        // middleware, which is irrelevant when calling the helpers directly.
    }

    public function probeIsBlocked(string $ip): bool
    {
        return $this->isBlockedIp($ip);
    }

    public function probeAssertPublicHost(string $host): string
    {
        return $this->assertPublicHost($host);
    }

    /** @return array<int, mixed> */
    public function probeCurlOptions(
        string $url,
        string $host,
        int $port,
        string $pinnedIp,
        int $maxBytes
    ): array {
        return $this->buildCurlOptions($url, $host, $port, $pinnedIp, $maxBytes);
    }

    /** @return array{0: string, 1: string} */
    public function probeDownload(string $url): array
    {
        return $this->downloadFromUrl($url);
    }
}
