<?php

declare(strict_types=1);

namespace PhelTest\Unit\Compiler\Analyzer\TupleSymbol;

use Phel\Compiler\Analyzer\Environment\NodeEnvironment;
use Phel\Compiler\Analyzer\TypeAnalyzer\TupleSymbol\QuoteSymbol;
use Phel\Compiler\Exceptions\AbstractLocatedException;
use Phel\Lang\Symbol;
use Phel\Lang\Tuple;
use PHPUnit\Framework\TestCase;

final class QuoteSymbolTest extends TestCase
{
    public function testTupleWithWrongSymbol(): void
    {
        $this->expectException(AbstractLocatedException::class);
        $this->expectExceptionMessage("This is not a 'quote.");

        $tuple = new Tuple(['any symbol', 'any text']);
        (new QuoteSymbol())->analyze($tuple, NodeEnvironment::empty());
    }

    public function testTupleWithoutArgument(): void
    {
        $this->expectException(AbstractLocatedException::class);
        $this->expectExceptionMessage("Exactly one argument is required for 'quote");

        $tuple = new Tuple([Symbol::create(Symbol::NAME_QUOTE)]);
        (new QuoteSymbol())->analyze($tuple, NodeEnvironment::empty());
    }

    public function testQuoteTupleWithAnyText(): void
    {
        $tuple = new Tuple([Symbol::create(Symbol::NAME_QUOTE), 'any text']);
        $symbol = (new QuoteSymbol())->analyze($tuple, NodeEnvironment::empty());

        self::assertSame('any text', $symbol->getValue());
    }
}
