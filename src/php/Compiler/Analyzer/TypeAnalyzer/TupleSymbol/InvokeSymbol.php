<?php

declare(strict_types=1);

namespace Phel\Compiler\Analyzer\TypeAnalyzer\TupleSymbol;

use Exception;
use Phel\Compiler\Analyzer\Ast\AbstractNode;
use Phel\Compiler\Analyzer\Ast\CallNode;
use Phel\Compiler\Analyzer\Ast\GlobalVarNode;
use Phel\Compiler\Analyzer\Environment\NodeEnvironmentInterface;
use Phel\Compiler\Analyzer\Exceptions\AnalyzerException;
use Phel\Compiler\Analyzer\TypeAnalyzer\WithAnalyzerTrait;
use Phel\Lang\AbstractType;
use Phel\Lang\Tuple;

final class InvokeSymbol implements TupleSymbolAnalyzerInterface
{
    use WithAnalyzerTrait;

    public function analyze(Tuple $tuple, NodeEnvironmentInterface $env): AbstractNode
    {
        $f = $this->analyzer->analyze(
            $tuple[0],
            $env->withContext(NodeEnvironmentInterface::CONTEXT_EXPRESSION)->withDisallowRecurFrame()
        );

        if ($f instanceof GlobalVarNode && $f->isMacro()) {
            return $this->globalMacro($tuple, $f, $env);
        }

        return new CallNode(
            $env,
            $f,
            $this->arguments($tuple, $env),
            $tuple->getStartLocation()
        );
    }

    private function globalMacro(Tuple $tuple, GlobalVarNode $f, NodeEnvironmentInterface $env): AbstractNode
    {
        return $this->analyzer->analyzeMacro($this->macroExpand($tuple, $f), $env);
    }

    /**
     * @return AbstractType|string|float|int|bool|null
     */
    private function macroExpand(Tuple $tuple, GlobalVarNode $macroNode)
    {
        $tupleCount = count($tuple);
        /** @psalm-suppress PossiblyNullArgument */
        $nodeName = $macroNode->getName()->getName();
        $fn = $GLOBALS['__phel'][$macroNode->getNamespace()][$nodeName];

        $arguments = [];
        for ($i = 1; $i < $tupleCount; $i++) {
            $arguments[] = $tuple[$i];
        }

        try {
            $result = $fn(...$arguments);
            $this->enrichLocation($result, $tuple);

            return $result;
        } catch (Exception $e) {
            throw AnalyzerException::withLocation(
                'Error in expanding macro "' . $macroNode->getNamespace() . '\\' . $nodeName . '": ' . $e->getMessage(),
                $tuple,
                $e
            );
        }
    }

    /**
     * @param AbstractType|string|float|int|bool|null $x
     */
    private function enrichLocation($x, AbstractType $parent): void
    {
        if ($x instanceof Tuple) {
            $this->enrichLocationForTuple($x, $parent);
        } elseif ($x instanceof AbstractType) {
            $this->enrichLocationForAbstractType($x, $parent);
        }
    }

    private function enrichLocationForTuple(Tuple $tuple, AbstractType $parent): void
    {
        foreach ($tuple as $item) {
            $this->enrichLocation($item, $parent);
        }

        $this->enrichLocationForAbstractType($tuple, $parent);
    }

    private function enrichLocationForAbstractType(AbstractType $type, AbstractType $parent): void
    {
        if (!$type->getStartLocation()) {
            $type->setStartLocation($parent->getStartLocation());
        }

        if (!$type->getEndLocation()) {
            $type->setEndLocation($parent->getEndLocation());
        }
    }

    private function arguments(Tuple $tuple, NodeEnvironmentInterface $env): array
    {
        $arguments = [];
        for ($i = 1, $iMax = count($tuple); $i < $iMax; $i++) {
            $arguments[] = $this->analyzer->analyze(
                $tuple[$i],
                $env->withContext(NodeEnvironmentInterface::CONTEXT_EXPRESSION)->withDisallowRecurFrame()
            );
        }

        return $arguments;
    }
}
