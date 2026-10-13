<?php

namespace App\Application\Errors;

use RuntimeException;

// CA-03.4: JPG or PNG, at most 2 MB (api-contract VALIDATION_ERROR, 422)
final class InvalidCover extends RuntimeException {}
