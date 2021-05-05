<?php

declare(strict_types=1);

namespace PhelTest\Integration;

use Generator;
use Phel\Compiler\Analyzer\AnalyzerInterface;
use Phel\Compiler\Analyzer\Environment\GlobalEnvironment;
use Phel\Compiler\Analyzer\Environment\NodeEnvironment;
use Phel\Compiler\CompilerFactory;
use Phel\Compiler\Emitter\EmitterInterface;
use Phel\Compiler\Lexer\TokenStream;
use Phel\Compiler\Parser\ParserInterface;
use Phel\Compiler\Parser\ParserNode\TriviaNodeInterface;
use Phel\Compiler\Reader\ReaderInterface;
use Phel\Lang\Symbol;
use Phel\Runtime\RuntimeFactory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class IntegrationTest extends TestCase
{
    private static GlobalEnvironment $globalEnv;
    private CompilerFactory $compilerFactory;

    public static function setUpBeforeClass(): void
    {
        Symbol::resetGen();
        $globalEnv = new GlobalEnvironment();
        $rt = RuntimeFactory::initializeNew($globalEnv);
        $rt->addPath('phel\\', [__DIR__ . '/../../src/phel/']);
        $rt->loadNs('phel\core');
        self::$globalEnv = $globalEnv;
    }

    public function setUp(): void
    {
        $this->compilerFactory = new CompilerFactory();
    }

    /**
     * @dataProvider providerIntegration
     */
    public function testIntegration(
        string $filename,
        string $phelCode,
        string $expectedGeneratedCode
    ): void {
        $globalEnv = self::$globalEnv;
        $globalEnv->setNs('user');
        Symbol::resetGen();

        $compiledCode = $this->compilePhelCode(
            $this->compilerFactory->createParser(),
            $this->compilerFactory->createReader($globalEnv),
            $this->compilerFactory->createAnalyzer($globalEnv),
            $this->compilerFactory->createEmitter($enableSourceMaps = false),
            $this->compilerFactory->createLexer()->lexString($phelCode, $filename)
        );

        self::assertEquals($expectedGeneratedCode, $compiledCode, 'in ' . $filename);
    }

    public function providerIntegration(): Generator
    {
        $fixturesDir = realpath(__DIR__ . '/Fixtures');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fixturesDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!preg_match('/\.test$/', $file->getRealPath())) {
                continue;
            }

            $test = file_get_contents($file->getRealpath());

            if (preg_match('/--PHEL--\s*(.*?)\s*--PHP--\s*(.*)/s', $test, $match)) {
                $filename = str_replace($fixturesDir . '/', '', $file->getRealPath());
                $phelCode = $match[1];
                $phpCode = trim($match[2]);

                yield $filename => [$filename, $phelCode, $phpCode];
            }
        }
    }

    private function compilePhelCode(
        ParserInterface $parser,
        ReaderInterface $reader,
        AnalyzerInterface $analyzer,
        EmitterInterface $emitter,
        TokenStream $tokenStream
    ): string {
        $compiledCode = [];

        while (true) {
            $parseTree = $parser->parseNext($tokenStream);
            if (!$parseTree) {
                break;
            }

            if (!$parseTree instanceof TriviaNodeInterface) {
                $readAst = $reader->read($parseTree);
                $node = $analyzer->analyze($readAst->getAst(), NodeEnvironment::empty());
                $compiledCode[] = $emitter->emitNodeAndEval($node);
            }
        }

        return trim(implode('', $compiledCode));
    }
}
