<?php

namespace Chiron\Views\Bootloader;

use Chiron\Boot\Directories;
use Chiron\Bootload\AbstractBootloader;
use Chiron\PublishableCollection;
use Chiron\Console\Console;
use Chiron\Views\Command\TwigClearCommand;
use Chiron\Views\Command\TwigCompileCommand;
use Chiron\Views\Command\TwigDebugCommand;

final class TwigCommandBootloader extends AbstractBootloader
{
    public function boot(Console $console): void
    {
        $console->addCommand(TwigClearCommand::getDefaultName(), TwigClearCommand::class);
        $console->addCommand(TwigCompileCommand::getDefaultName(), TwigCompileCommand::class);
        $console->addCommand(TwigDebugCommand::getDefaultName(), TwigDebugCommand::class);
    }
}
