<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media;

use mysql_xdevapi\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MagentoCode\CliMediaTool\Command\CatalogAbstract;

class Info extends CatalogAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('catalog:media:info')
            ->setDescription('Get information about missing/invalid product media images');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $mediaGalleryPaths = $this->getMediaGalleryPaths();
            $orphanedMediaGalleryPaths = $this->getOrphanedMediaGalleryPaths();
            $filePaths = $this->getFilePaths();
            $orphanedFilePaths = $this->getOrphanedFilePaths();
            $cacheFilePaths = $this->getCacheFilePaths();
            $unusedFiles = $this->getUnusedFilePaths();
            $missingFiles = $this->getMissingFilePaths();

            $output->writeln('===============================================');
            $output->writeln(sprintf('Media Gallery entries: %s.', count($mediaGalleryPaths)));
            $output->writeln(sprintf('Orphaned media gallery entries: %s.', count($orphanedMediaGalleryPaths)));
            $output->writeln(sprintf('Non-cache media files on filesystem: %s.', count($filePaths)));
            $output->writeln(sprintf('Cache media files on filesystem: %s.', count($cacheFilePaths)));
            $output->writeln(sprintf('Unused files on filesystem: %s.', count($unusedFiles)));
            $output->writeln(sprintf('Missing files on filesystem: %s.', count($missingFiles)));
            $output->writeln(sprintf('Orphaned files on filesystem: %s.', count($orphanedFilePaths)));
            $output->writeln('===============================================');
            $output->writeln('To automatically clean up the media gallery, run catalog:media:cleanup');
        } catch (\Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
