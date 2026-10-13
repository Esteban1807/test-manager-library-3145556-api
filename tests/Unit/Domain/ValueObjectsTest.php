<?php

namespace Tests\Unit\Domain;

use App\Domain\Book\Isbn;
use App\Domain\Book\Quantity;
use App\Domain\Loan\DateRange;
use App\Domain\Member\DocumentNumber;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValueObjectsTest extends TestCase
{
    public function test_isbn_accepts_13_digits(): void
    {
        $this->assertSame('9780307474728', Isbn::fromString('9780307474728')->value);
    }

    public function test_isbn_rejects_wrong_length_or_letters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Isbn::fromString('978030747472');
    }

    public function test_isbn_rejects_non_digits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Isbn::fromString('978030747472X');
    }

    public function test_quantity_accepts_one_or_more(): void
    {
        $this->assertSame(1, Quantity::of(1)->value);
    }

    public function test_quantity_rejects_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of(0);
    }

    public function test_document_number_is_trimmed(): void
    {
        $this->assertSame('12345', DocumentNumber::fromString('  12345 ')->value);
    }

    public function test_document_number_rejects_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DocumentNumber::fromString('   ');
    }

    public function test_date_range_accepts_end_equal_to_start(): void
    {
        $day = new DateTimeImmutable('2026-10-01T00:00:00Z');
        $this->assertSame($day, DateRange::of($day, $day)->from);
    }

    public function test_date_range_rejects_end_before_start(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DateRange::of(
            new DateTimeImmutable('2026-10-31T00:00:00Z'),
            new DateTimeImmutable('2026-10-01T00:00:00Z'),
        );
    }
}
