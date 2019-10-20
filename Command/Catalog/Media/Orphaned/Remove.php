<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media\Orphaned;

use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\DB\Select;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MagentoCode\CliMediaTool\Command\CatalogAbstract;

class Remove extends CatalogAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('catalog:media:orphaned:remove')
            ->setDescription(
                'Remove all product media images which exist in the database and on the filesystem, but are not in use on any magento products.'
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
            $this->deleteFilePaths($orphanedFilePaths);
            $output->writeln(sprintf('Removed %d orphaned files.', count($orphanedFilePaths)));
        } catch (\Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
