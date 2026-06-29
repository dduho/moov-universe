<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Minimal SMPP v3.4 client for sending SMS via Moov Money SMSC.
 *
 * Implements connect → bind_transceiver → submit_sm → unbind → disconnect
 * per SMS send. This is intentionally short-lived to avoid SMSC single-bind
 * limitation conflicts.
 */
class SmppService
{
    // SMPP Command IDs
    private const BIND_TRANSCEIVER      = 0x00000009;
    private const BIND_TRANSCEIVER_RESP = 0x80000009;
    private const SUBMIT_SM             = 0x00000004;
    private const SUBMIT_SM_RESP        = 0x80000004;
    private const UNBIND                = 0x00000006;
    private const UNBIND_RESP           = 0x80000006;
    private const STATUS_OK             = 0x00000000;

    private string $host;
    private int    $port;
    private string $systemId;
    private string $password;
    private string $sourceAddr;

    /** @var resource|null */
    private $socket = null;
    private int $sequence = 1;

    public function __construct()
    {
        $this->host       = config('services.smpp.host',        '10.82.11.30');
        $this->port       = (int) config('services.smpp.port',  2775);
        $this->systemId   = config('services.smpp.system_id',   '');
        $this->password   = config('services.smpp.password',    '');
        $this->sourceAddr = config('services.smpp.source_addr', 'MoovApps');
    }

    /**
     * Send an SMS. Retries up to 3 times on transient SMSC errors.
     * Returns true on success, false if all attempts fail.
     *
     * @param string|null $sourceAddr Override the configured sender for this send.
     */
    public function sendSms(string $phone, string $message, ?string $sourceAddr = null): bool
    {
        $destination = $this->normalizePhone($phone);
        $sender      = $sourceAddr ?? $this->sourceAddr;
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->connect();
                $this->bind();
                $this->submitSm($destination, $message, $sender);
                $this->unbind();
                return true;
            } catch (\Throwable $e) {
                Log::warning("SMPP sendSms attempt {$attempt}/{$maxAttempts} failed", [
                    'error'  => $e->getMessage(),
                    'phone'  => $destination,
                    'sender' => $sender,
                ]);
                if ($attempt < $maxAttempts) {
                    usleep(800_000); // 0.8s before next attempt
                }
            } finally {
                $this->disconnect();
            }
        }

        Log::error('SMPP sendSms failed after all attempts', ['phone' => $destination, 'sender' => $sender]);
        return false;
    }

    /**
     * Send an SMS using the primary sender (MoovApps). If that sender fails
     * after all its retries, automatically falls back to the secondary
     * short code (999901) before giving up.
     */
    public function sendSmsWithFallback(string $phone, string $message): bool
    {
        $primary  = config('services.smpp.source_addr', 'MoovApps');
        $fallback = config('services.smpp.fallback_source_addr', '999901');

        if ($this->sendSms($phone, $message, $primary)) {
            return true;
        }

        Log::warning('SMPP sendSms failed with primary sender, falling back', [
            'primary'  => $primary,
            'fallback' => $fallback,
        ]);

        return $this->sendSms($phone, $message, $fallback);
    }

    // -------------------------------------------------------------------------
    // Phone normalisation
    // -------------------------------------------------------------------------

    /**
     * Normalize to E.164 without '+'.
     * Accepts: 99999999 | 22899999999 | +22899999999
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        // 8-digit local Togo number → prepend country code
        if (strlen($phone) === 8 && ctype_digit($phone)) {
            $phone = '228' . $phone;
        }

        return $phone;
    }

    // -------------------------------------------------------------------------
    // Connection
    // -------------------------------------------------------------------------

    private function connect(): void
    {
        $this->socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            10
        );

        if (!$this->socket) {
            throw new \RuntimeException("SMPP connect failed [{$errno}]: {$errstr}");
        }

        stream_set_timeout($this->socket, 10);
        $this->sequence = 1;
    }

    private function disconnect(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    // -------------------------------------------------------------------------
    // SMPP operations
    // -------------------------------------------------------------------------

    private function bind(): void
    {
        $body = $this->cStr($this->systemId)   // system_id
              . $this->cStr($this->password)    // password
              . $this->cStr('')                  // system_type
              . chr(0x34)                        // interface_version = 3.4
              . chr(0x00)                        // addr_ton
              . chr(0x00)                        // addr_npi
              . $this->cStr('');                 // address_range

        $this->sendPdu(self::BIND_TRANSCEIVER, $body);
        $resp = $this->readPdu();

        if ($resp['command_id'] !== self::BIND_TRANSCEIVER_RESP || $resp['status'] !== self::STATUS_OK) {
            throw new \RuntimeException('SMPP bind failed, status: 0x' . dechex($resp['status']));
        }
    }

    private function submitSm(string $destination, string $message, string $sourceAddr): void
    {
        // Truncate to 160 GSM 7-bit characters
        $msgBytes = mb_substr($message, 0, 160);

        $body = $this->cStr('')                      // service_type
              . chr(5)                                // source_addr_ton: Alphanumeric
              . chr(0)                                // source_addr_npi: Unknown
              . $this->cStr($sourceAddr)              // source_addr
              . chr(1)                                // dest_addr_ton: International
              . chr(1)                                // dest_addr_npi: ISDN (E.164)
              . $this->cStr($destination)             // destination_addr
              . chr(0)                                // esm_class
              . chr(0)                                // protocol_id
              . chr(0)                                // priority_flag
              . $this->cStr('')                       // schedule_delivery_time
              . $this->cStr('')                       // validity_period
              . chr(0)                                // registered_delivery
              . chr(0)                                // replace_if_present_flag
              . chr(0)                                // data_coding: GSM 7-bit default
              . chr(0)                                // sm_default_msg_id
              . chr(strlen($msgBytes))                // sm_length
              . $msgBytes;                            // short_message (no null terminator)

        $this->sendPdu(self::SUBMIT_SM, $body);
        $resp = $this->readPdu();

        if ($resp['command_id'] !== self::SUBMIT_SM_RESP || $resp['status'] !== self::STATUS_OK) {
            throw new \RuntimeException('SMPP submit_sm failed, status: 0x' . dechex($resp['status']));
        }
    }

    private function unbind(): void
    {
        $this->sendPdu(self::UNBIND, '');
        $this->readPdu(); // ignore unbind_resp status
    }

    // -------------------------------------------------------------------------
    // PDU I/O
    // -------------------------------------------------------------------------

    private function sendPdu(int $commandId, string $body): void
    {
        $length = 16 + strlen($body);
        $header = pack('NNNN', $length, $commandId, self::STATUS_OK, $this->sequence++);
        fwrite($this->socket, $header . $body);
    }

    private function readPdu(): array
    {
        $header = $this->readBytes(16);

        if (strlen($header) < 16) {
            throw new \RuntimeException('SMPP: short read on PDU header');
        }

        $parsed  = unpack('Nlength/Ncommand_id/Nstatus/Nsequence', $header);
        $bodyLen = $parsed['length'] - 16;
        $body    = $bodyLen > 0 ? $this->readBytes($bodyLen) : '';

        return [
            'command_id' => $parsed['command_id'],
            'status'     => $parsed['status'],
            'sequence'   => $parsed['sequence'],
            'body'       => $body,
        ];
    }

    private function readBytes(int $n): string
    {
        $data     = '';
        $deadline = microtime(true) + 10;

        while (strlen($data) < $n && microtime(true) < $deadline) {
            $chunk = fread($this->socket, $n - strlen($data));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data .= $chunk;
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Null-terminated C-string for SMPP PDU bodies */
    private function cStr(string $s): string
    {
        return $s . "\x00";
    }
}
