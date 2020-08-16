<?php

declare(strict_types=1);

namespace Chiron\Views\Bootloader;

use Chiron\Boot\Directories;
use Chiron\Bootload\AbstractBootloader;
use Chiron\PublishableCollection;

final class PublishTwigBootloader extends AbstractBootloader
{
    public function boot(PublishableCollection $publishable, Directories $directories): void
    {
        // copy the configuration file template from the package "config" folder to the user "config" folder.
        $publishable->add(__DIR__ . '/../../config/twig.php.dist', $directories->get('@config/twig.php'));
    }
}
