<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MagentoCode\CliMediaTool\Command\CatalogAbstract;

class Orphaned extends CatalogAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('catalog:media:orphaned')
            ->setDescription(
                'Get a list of product media images which exist in the database and on the filesystem, but are not in use on any magento products.'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $orphanedFilePaths = $this->getOrphanedFilePaths();
            $output->writeln($orphanedFilePaths);
        } catch (\Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
