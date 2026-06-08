<?php

namespace Tests\Unit;

use App\Services\InstallationSaves\SecureSaveCrypto;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SecureSaveCryptoTest extends TestCase
{
    private const APP_KEY = 'base64:c3VwZXItc2VjcmV0LWtleS0zMi1ieXRlcy1sb25nIQ==';

    private const PAYLOAD = 'super secret installation payload';

    private SecureSaveCrypto $crypto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crypto = new SecureSaveCrypto;
    }

    public function test_encrypt_then_decrypt_round_trips_the_payload(): void
    {
        $save = $this->crypto->encrypt(self::PAYLOAD, self::APP_KEY);

        $this->assertSame(self::PAYLOAD, $this->crypto->decrypt($save, self::APP_KEY));
    }

    public function test_each_encryption_uses_a_fresh_salt_and_nonce(): void
    {
        $first = json_decode($this->crypto->encrypt(self::PAYLOAD, self::APP_KEY), true);
        $second = json_decode($this->crypto->encrypt(self::PAYLOAD, self::APP_KEY), true);

        $this->assertNotSame($first['salt'], $second['salt']);
        $this->assertNotSame($first['nonce'], $second['nonce']);
        $this->assertNotSame($first['payload'], $second['payload']);
    }

    public function test_plaintext_is_not_present_in_the_encoded_save(): void
    {
        $save = $this->crypto->encrypt(self::PAYLOAD, self::APP_KEY);

        $this->assertStringNotContainsString(self::PAYLOAD, $save);
    }

    public function test_tampering_with_the_ciphertext_fails_authentication(): void
    {
        $save = $this->crypto->encrypt(self::PAYLOAD, self::APP_KEY);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to decrypt');
        $this->crypto->decrypt($this->flipFirstByteOf($save, 'payload'), self::APP_KEY);
    }

    public function test_tampering_with_the_gcm_tag_fails_authentication(): void
    {
        $save = $this->crypto->encrypt(self::PAYLOAD, self::APP_KEY);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to decrypt');
        $this->crypto->decrypt($this->flipFirstByteOf($save, 'tag'), self::APP_KEY);
    }

    public function test_tampering_with_the_nonce_fails_authentication(): void
    {
        $save = $this->crypto->encrypt(self::PAYLOAD, self::APP_KEY);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to decrypt');
        $this->crypto->decrypt($this->flipFirstByteOf($save, 'nonce'), self::APP_KEY);
    }

    public function test_a_different_app_key_cannot_decrypt(): void
    {
        $save = $this->crypto->encrypt(self::PAYLOAD, self::APP_KEY);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to decrypt');
        $this->crypto->decrypt($save, 'base64:'.base64_encode(str_repeat('x', 32)));
    }

    public function test_unsupported_format_is_rejected(): void
    {
        $save = json_decode($this->crypto->encrypt(self::PAYLOAD, self::APP_KEY), true);
        $save['format'] = 'something-else';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported installation save format.');
        $this->crypto->decrypt(json_encode($save), self::APP_KEY);
    }

    public function test_unsupported_cipher_is_rejected(): void
    {
        $save = json_decode($this->crypto->encrypt(self::PAYLOAD, self::APP_KEY), true);
        $save['cipher'] = 'aes-128-cbc';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported installation save encryption.');
        $this->crypto->decrypt(json_encode($save), self::APP_KEY);
    }

    public function test_a_corrupt_base64_field_is_rejected(): void
    {
        $save = json_decode($this->crypto->encrypt(self::PAYLOAD, self::APP_KEY), true);
        $save['salt'] = '!!! not base64 !!!';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid installation save salt.');
        $this->crypto->decrypt(json_encode($save), self::APP_KEY);
    }

    public function test_parse_app_key_decodes_base64_prefixed_keys(): void
    {
        $raw = str_repeat('k', 32);

        $this->assertSame($raw, $this->crypto->parseAppKey('base64:'.base64_encode($raw)));
    }

    public function test_parse_app_key_returns_plain_keys_unchanged(): void
    {
        $this->assertSame('plain-application-key', $this->crypto->parseAppKey('  plain-application-key  '));
    }

    public function test_parse_app_key_rejects_an_empty_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_KEY is required.');
        $this->crypto->parseAppKey('   ');
    }

    public function test_parse_app_key_rejects_invalid_base64(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_KEY is not valid base64.');
        $this->crypto->parseAppKey('base64:@@@not-base64@@@');
    }

    /**
     * Flip the first byte of a base64-encoded field in the encoded save, leaving
     * everything else intact — the minimal mutation an attacker could attempt.
     */
    private function flipFirstByteOf(string $save, string $field): string
    {
        $data = json_decode($save, true, flags: JSON_THROW_ON_ERROR);
        $raw = base64_decode($data[$field], true);
        $raw[0] = chr(ord($raw[0]) ^ 0xFF);
        $data[$field] = base64_encode($raw);

        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
