<?php

namespace Chiron\Views\Bootloader;

use Chiron\Boot\Directories;
use Chiron\Bootload\AbstractBootloader;
use Chiron\PublishableCollection;

final class PublishTwigBootloader extends AbstractBootloader
{
    public function boot(PublishableCollection $publishable, Directories $directories): void
    {
        // copy the configuration file from the package "config" folder to the user "config" folder.
        $publishable->add(__DIR__ . '/../../config/twig.php', $directories->get('@config/twig.php'));
    }
}
