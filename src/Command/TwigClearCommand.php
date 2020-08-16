<?php

declare(strict_types=1);

namespace Chiron\Views\Command;

use Chiron\Filesystem\Filesystem;
use Chiron\Console\AbstractCommand;
use Chiron\PublishableCollection;
use Symfony\Component\Console\Input\InputOption;
use Chiron\Views\TemplateRendererInterface;
use Twig\Environment;

//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Provider/Twig/Command/CleanCommand.php

// This class only work if the cache parameter is a string value (absolute path to the cache folder).
// TODO : utiliser le mot "clean" plutot que clear ????

/**
 * Console command to clear the Twig cache.
 */
final class TwigClearCommand extends AbstractCommand
{
    protected static $defaultName = 'twig:clear';

    protected function configure(): void
    {
        $this->setDescription('Clean the Twig cache folder.');
    }

    public function perform(Filesystem $filesystem, TemplateRendererInterface $renderer): int
    {
        $cacheDir = $renderer->twig()->getCache();

        // The cache value defined in the Twig options could be : false (for no cache) / string (absolute path to the cache folder) / Twig\Cache\CacheInterface::class (if defined by the user)
        if (! is_string($cacheDir)) {
            $this->error('Twig cache option is not defined as an absolute path, so it can\'t be cleaned.');

            return self::FAILURE;
        }

        // TODO : ajouter un try catch des \Throwable et afficher une erreur si c'est le cas ????
        // TODO : attention on va devoir gérer le cas ou le répertoire n'existe pas, pour l'instant la méthode deleteDirectory retourne "false" dans ce cas là, mais si léve une exception prochainement il faudra gérer ce cas !!!!
        $deleted = $filesystem->deleteDirectory($cacheDir, true);

        if ($deleted === false) {
            $this->error('Twig cache failed to be cleaned.');

            return self::FAILURE;
        }

        $this->success('Twig cache cleaned.');

        return self::SUCCESS;
    }
}
