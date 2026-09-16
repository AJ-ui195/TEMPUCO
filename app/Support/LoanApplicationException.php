<?php

namespace App\Support;

use RuntimeException;

final class LoanApplicationException extends RuntimeException
{
    public function __construct(
        public string $title,
        string $body,
    ) {
        parent::__construct($body);
    }
}
