<?php

declare(strict_types=1);

namespace PhelTest\Unit\Compiler\Analyzer\TupleSymbol;

use Generator;
use Phel\Compiler\Analyzer\Analyzer;
use Phel\Compiler\Analyzer\Ast\IfNode;
use Phel\Compiler\Analyzer\Environment\GlobalEnvironment;
use Phel\Compiler\Analyzer\Environment\NodeEnvironment;
use Phel\Compiler\Analyzer\TypeAnalyzer\TupleSymbol\IfSymbol;
use Phel\Compiler\Exceptions\AbstractLocatedException;
use Phel\Lang\Symbol;
use Phel\Lang\Tuple;
use PHPUnit\Framework\TestCase;

final class IfSymbolTest extends TestCase
{
    /**
     * @dataProvider providerRequiresAtLeastTwoOrThreeArgs
     */
    public function testRequiresAtLeastTwoOrThreeArgs(Tuple $tuple): void
    {
        $this->expectException(AbstractLocatedException::class);
        $this->expectExceptionMessage("'if requires two or three arguments");

        $this->analyze($tuple);
    }

    public function providerRequiresAtLeastTwoOrThreeArgs(): Generator
    {
        yield 'No arguments provided: (if)' => [
            'tuple' => Tuple::create(
                Symbol::create(Symbol::NAME_IF)
            ),
        ];

        yield 'Only one argument provided: (if "one")' => [
            'tuple' => Tuple::create(
                Symbol::create(Symbol::NAME_IF),
                'one',
            ),
        ];

        yield 'Only one argument provided: (if "one" "two" "three" "four")' => [
            'tuple' => Tuple::create(
                Symbol::create(Symbol::NAME_IF),
                'one',
                'two',
                'three',
                'four',
            ),
        ];
    }

    public function testAnalyze(): void
    {
        $tuple = Tuple::create(
            Symbol::create(Symbol::NAME_IF),
            true,
            'then-expression',
            'else-expression',
        );

        $actual = $this->analyze($tuple);

        self::assertTrue($actual->getTestExpr()->getValue());
        self::assertSame('then-expression', $actual->getThenExpr()->getValue());
        self::assertSame('else-expression', $actual->getElseExpr()->getValue());
        self::assertEquals(NodeEnvironment::empty(), $actual->getEnv());
    }

    private function analyze(Tuple $tuple): IfNode
    {
        $analyzer = new Analyzer(new GlobalEnvironment());

        return (new IfSymbol($analyzer))->analyze($tuple, NodeEnvironment::empty());
    }
}
