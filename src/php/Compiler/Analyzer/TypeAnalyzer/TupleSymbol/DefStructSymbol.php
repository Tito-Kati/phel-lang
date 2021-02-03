<?php

declare(strict_types=1);

namespace Phel\Compiler\Analyzer\TypeAnalyzer\TupleSymbol;

use Phel\Compiler\Analyzer\Ast\DefStructNode;
use Phel\Compiler\Analyzer\Environment\NodeEnvironmentInterface;
use Phel\Compiler\Analyzer\Exceptions\AnalyzerException;
use Phel\Compiler\Analyzer\TypeAnalyzer\WithAnalyzerTrait;
use Phel\Lang\Symbol;
use Phel\Lang\Tuple;

final class DefStructSymbol implements TupleSymbolAnalyzerInterface
{
    use WithAnalyzerTrait;

    public function analyze(Tuple $tuple, NodeEnvironmentInterface $env): DefStructNode
    {
        if (count($tuple) !== 3) {
            throw AnalyzerException::withLocation(
                "Exactly two arguments are required for 'defstruct. Got " . count($tuple),
                $tuple
            );
        }

        if (!($tuple[1] instanceof Symbol)) {
            throw AnalyzerException::withLocation("First argument of 'defstruct must be a Symbol.", $tuple);
        }

        if (!($tuple[2] instanceof Tuple)) {
            throw AnalyzerException::withLocation("Second argument of 'defstruct must be a Tuple.", $tuple);
        }

        return new DefStructNode(
            $env,
            $this->analyzer->getNamespace(),
            $tuple[1],
            $this->params($tuple[2]),
            $tuple->getStartLocation()
        );
    }

    private function params(Tuple $tuple): array
    {
        $params = [];
        foreach ($tuple as $element) {
            if (!($element instanceof Symbol)) {
                throw AnalyzerException::withLocation('Defstruct field elements must be Symbols.', $tuple);
            }
            $params[] = $element;
        }

        return $params;
    }
}
