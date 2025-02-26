<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media;

use MagentoCode\CliMediaTool\Command\CatalogAbstract;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cleanup extends CatalogAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('catalog:media:cleanup')
            ->setDescription('Cleanup the media gallery and filesystem images in one fell swoop');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('===============================================');
            $output->writeln('===     Beginning Media Gallery Cleanup     ===');
            $output->writeln('===============================================');

            /*
             * Output the starting state of the media gallery
             */
            $filePaths = $this->getFilePaths(false);
            $mediaGalleryPaths = $this->getMediaGalleryPaths(false);
            $output->writeln(sprintf('   Files: %d', count($filePaths)));
            $output->writeln(sprintf('   Media Gallery Entries: %d', count($mediaGalleryPaths)));
            $output->writeln('===============================================');

            /*
             * Cleanup orphaned media gallery entries that no longer have products using them
             */
            $orphanedMediaGalleryPaths = $this->getOrphanedMediaGalleryPaths(false);
            $this->deleteMediaGalleryPaths($orphanedMediaGalleryPaths);
            $output->writeln(
                sprintf(
                    'Removed %d orphaned media gallery paths which are no longer in use on any products.',
                    count($orphanedMediaGalleryPaths)
                )
            );

            /*
             * Remove all media gallery entries for images we no longer have on the filesystem
             */
            $missingFilePaths = $this->getMissingFilePaths(false);
            $this->deleteMediaGalleryPaths($missingFilePaths);
            $output->writeln(
                sprintf(
                    'Removed %d media gallery paths that no longer have images on the filesystem.',
                    count($missingFilePaths)
                )
            );

            /*
             * Remove all files no longer in use
             */
            $unusedFilePaths = $this->getUnusedFilePaths(false);
            $this->deleteFilePaths($unusedFilePaths);
            $output->writeln(
                sprintf(
                    'Removed %d files which are no longer present in the media gallery database table.',
                    count($unusedFilePaths)
                )
            );

            /*
             * Remove temporary files
             */
            $maxLifetimeInHours = Temporary::DEFAULT_MAX_LIFETIME;
            $temporaryFilePaths = $this->getTemporaryFilePaths(false, $maxLifetimeInHours);
            $this->deleteFilePaths($temporaryFilePaths);
            $output->writeln(
                sprintf(
                    'Removed %d temporary files older that %d hour(s).',
                    count($temporaryFilePaths),
                    $maxLifetimeInHours
                )
            );

            /*
             * Output the ending state of the media gallery
             */
            $filePaths = $this->getFilePaths(false);
            $mediaGalleryPaths = $this->getMediaGalleryPaths(false);
            $output->writeln('===============================================');
            $output->writeln('===                  Done                   ===');
            $output->writeln('===============================================');
            $output->writeln(sprintf('   Files: %d', count($filePaths)));
            $output->writeln(sprintf('   Media Gallery Entries: %d', count($mediaGalleryPaths)));
            $output->writeln('===============================================');
        } catch (\Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
