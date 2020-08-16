<?php

declare(strict_types=1);

namespace Chiron\Views\Bootloader;

use Chiron\Bootload\AbstractBootloader;
use Chiron\Console\Console;
use Chiron\Views\Command\TwigClearCommand;
use Chiron\Views\Command\TwigCompileCommand;
use Chiron\Views\Command\TwigDebugCommand;
use Chiron\Views\Command\TwigVersionCommand;

final class TwigCommandBootloader extends AbstractBootloader
{
    public function boot(Console $console): void
    {
        $console->addCommand(TwigClearCommand::getDefaultName(), TwigClearCommand::class);
        $console->addCommand(TwigCompileCommand::getDefaultName(), TwigCompileCommand::class);
        $console->addCommand(TwigDebugCommand::getDefaultName(), TwigDebugCommand::class);
        $console->addCommand(TwigVersionCommand::getDefaultName(), TwigVersionCommand::class);
    }
}
