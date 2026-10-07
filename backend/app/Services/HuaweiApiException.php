<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class HuaweiApiException extends RuntimeException
{
    /**
     * @param string $reason  not_configured | invalid_shortcode | unreachable | bad_response | soap_fault | rejected
     */
    public function __construct(
        string $message,
        public readonly string $reason = 'error',
        ?Throwable $previous = null,
        public readonly ?string $resultCode = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
