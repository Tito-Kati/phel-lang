<?php

declare(strict_types=1);

namespace PhelTest\Unit\Compiler\Analyzer\SpecialForm\Binding\Deconstructor;

use Phel\Compiler\Analyzer\TypeAnalyzer\SpecialForm\Binding\BindingValidatorInterface;
use Phel\Compiler\Analyzer\TypeAnalyzer\SpecialForm\Binding\Deconstructor;
use Phel\Compiler\Analyzer\TypeAnalyzer\SpecialForm\Binding\Deconstructor\PhelArrayBindingDeconstructor;
use Phel\Lang\PhelArray;
use Phel\Lang\Symbol;
use Phel\Lang\TypeFactory;
use PHPUnit\Framework\TestCase;

final class PhelArrayBindingDeconstructorTest extends TestCase
{
    private PhelArrayBindingDeconstructor $deconstructor;

    public function setUp(): void
    {
        Symbol::resetGen();

        $this->deconstructor = new PhelArrayBindingDeconstructor(
            new Deconstructor(
                $this->createMock(BindingValidatorInterface::class)
            )
        );
    }

    public function test_deconstruct_symbol(): void
    {
        // Test for binding like this (let [@[0 a] x])
        // This will be destructured to this:
        // (let [__phel_1 x
        //       __phel 2 (get __phel_1 0)
        //       a __phel_2])

        $index = 0;
        $bindTo = Symbol::create('a');
        $binding = PhelArray::create($index, $bindTo); // @[0 a]
        $value = Symbol::create('x');

        $bindings = [];
        $this->deconstructor->deconstruct($bindings, $binding, $value);

        self::assertEquals([
            // __phel_1 x
            [
                Symbol::create('__phel_1'),
                $value,
            ],
            // __phel_2 (get __phel_1 0)
            [
                Symbol::create('__phel_2'),
                TypeFactory::getInstance()->persistentListFromArray([
                    Symbol::create(Symbol::NAME_PHP_ARRAY_GET),
                    Symbol::create('__phel_1'),
                    $index,
                ]),
            ],
            // a __phel_2
            [
                $bindTo,
                Symbol::create('__phel_2'),
            ],
        ], $bindings);
    }

    public function test_deconstruct_nested_vector(): void
    {
        // Test for binding like this (let [@[0 [a]] x])
        // This will be destructured to this:
        // (let [__phel_1 x
        //       __phel 2 (get __phel_1 0)
        //       __phel_3 __phel_2
        //       __phel_4 (first __phel_3)
        //       __phel_5 (next __phel_3)
        //       a __phel_4])


        $value = Symbol::create('x');
        $bindTo = Symbol::create('a');
        $index = 0;
        $binding = PhelArray::create($index, TypeFactory::getInstance()->persistentVectorFromArray([$bindTo]));

        $bindings = [];
        $this->deconstructor->deconstruct($bindings, $binding, $value);

        self::assertEquals([
            // __phel_1 x
            [
                Symbol::create('__phel_1'),
                $value,
            ],
            // __phel 2 (get __phel 0)
            [
                Symbol::create('__phel_2'),
                TypeFactory::getInstance()->persistentListFromArray([
                    Symbol::create(Symbol::NAME_PHP_ARRAY_GET),
                    Symbol::create('__phel_1'),
                    $index,
                ]),
            ],
            // __phel_3 __phel_2
            [
                Symbol::create('__phel_3'),
                Symbol::create('__phel_2'),
            ],
            // __phel_4 (first __phel_3)
            [
                Symbol::create('__phel_4'),
                TypeFactory::getInstance()->persistentListFromArray([
                    Symbol::create('first'),
                    Symbol::create('__phel_3'),
                ]),
            ],
            // __phel_5 (next __phel_3)
            [
                Symbol::create('__phel_5'),
                TypeFactory::getInstance()->persistentListFromArray([
                    Symbol::create('next'),
                    Symbol::create('__phel_3'),
                ]),
            ],
            // a __phel_4
            [
                $bindTo,
                Symbol::create('__phel_4'),
            ],
        ], $bindings);
    }
}
