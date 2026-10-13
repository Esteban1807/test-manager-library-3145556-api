<?php

namespace App\Domain\Book;

use DomainException;

// No copies left to lend (spec.md CA-06.3, api-contract BOOK_NOT_AVAILABLE)
final class BookNotAvailable extends DomainException {}
