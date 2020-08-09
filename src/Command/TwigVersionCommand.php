<?php

declare(strict_types=1);

namespace Chiron\Views\Command;

use Chiron\Console\AbstractCommand;
use Twig\Environment;

/**
 * Console command to clear the Twig cache.
 */
final class TwigVersionCommand extends AbstractCommand
{
    protected static $defaultName = 'twig:version';

    protected function configure(): void
    {
        $this->setDescription('Get information about Twig version.');
    }

    public function perform(): int
    {
        $this->line(sprintf('<info>Twig</info> version<comment> [ %s ]</comment>', Environment::VERSION));

        return self::SUCCESS;
    }
}
