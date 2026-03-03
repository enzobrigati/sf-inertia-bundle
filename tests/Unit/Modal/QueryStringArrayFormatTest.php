<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Modal;

use EnzoBrigati\InertiaBundle\Modal\QueryStringArrayFormat;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryStringArrayFormatTest extends TestCase
{
    #[Test]
    public function it_has_brackets_case(): void
    {
        $this->assertSame('brackets', QueryStringArrayFormat::Brackets->value);
    }

    #[Test]
    public function it_has_indices_case(): void
    {
        $this->assertSame('indices', QueryStringArrayFormat::Indices->value);
    }

    #[Test]
    public function it_has_exactly_two_cases(): void
    {
        $this->assertCount(2, QueryStringArrayFormat::cases());
    }
}
