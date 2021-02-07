<?php

declare(strict_types=1);

namespace PhelTest\Integration\Command\Test;

use Phel\Command\CommandFactory;
use Phel\Command\CommandFactoryInterface;
use Phel\Compiler\Analyzer\Environment\GlobalEnvironment;
use Phel\Compiler\CompilerFactory;
use Phel\Formatter\FormatterFactoryInterface;
use Phel\Interop\InteropFactoryInterface;
use Phel\PhelConfig;
use Phel\Runtime\RuntimeFactory;
use Phel\Runtime\RuntimeInterface;
use PHPUnit\Framework\TestCase;

final class TestCommandTest extends TestCase
{
    public function testAllInProject(): void
    {
        $currentDir = __DIR__ . '/Fixtures/test-cmd-project-success/';

        $runtime = $this->createRuntime();
        $runtime->addPath('test-cmd-project-success\\', [$currentDir . 'tests']);
        $runtime->addPath('phel\\', [__DIR__ . '/../../../src/phel']);
        $runtime->loadNs('phel\core');

        $testCommand = $this
            ->createCommandFactory($currentDir)
            ->createTestCommand($runtime);

        $this->expectOutputString("..\n\n\n\nPassed: 2\nFailed: 0\nError: 0\nTotal: 2\n");
        self::assertTrue($testCommand->run([]));
    }

    public function testOneFileInProject(): void
    {
        $currentDir = __DIR__ . '/Fixtures/test-cmd-project-success/';

        $runtime = $this->createRuntime();
        $runtime->addPath('test-cmd-project-success\\', [$currentDir . 'tests']);
        $runtime->addPath('phel\\', [__DIR__ . '/../../../src/phel']);
        $runtime->loadNs('phel\core');

        $testCommand = $this
            ->createCommandFactory($currentDir)
            ->createTestCommand($runtime);

        $this->expectOutputString(".\n\n\n\nPassed: 1\nFailed: 0\nError: 0\nTotal: 1\n");
        self::assertTrue($testCommand->run([$currentDir . 'tests/test1.phel']));
    }

    public function testAllInFailedProject(): void
    {
        $currentDir = __DIR__ . '/Fixtures/test-cmd-project-failure/';

        $runtime = $this->createRuntime();
        $runtime->addPath('test-cmd-project-failure\\', [$currentDir . 'tests']);
        $runtime->addPath('phel\\', [__DIR__ . '/../../../src/phel']);
        $runtime->loadNs('phel\core');

        $testCommand = $this
            ->createCommandFactory($currentDir)
            ->createTestCommand($runtime);

        $this->expectOutputRegex('/.*Failed\\: 1.*/');
        self::assertFalse($testCommand->run([]));
    }

    private function createRuntime(): RuntimeInterface
    {
        return RuntimeFactory::initializeNew(new GlobalEnvironment());
    }

    private function createCommandFactory(string $currentDir): CommandFactoryInterface
    {
        return new CommandFactory(
            $currentDir,
            PhelConfig::createFromRootDir($currentDir),
            new CompilerFactory(),
            $this->createStub(FormatterFactoryInterface::class),
            $this->createStub(InteropFactoryInterface::class)
        );
    }
}
