<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media\Missing;

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
        $this->setName('catalog:media:missing:remove')
            ->setDescription(
                'Remove all references to product media images which exist in the database but not on the filesystem.'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $missingFilePaths = $this->getMissingFilePaths();
            $this->deleteMediaGalleryPaths($missingFilePaths);
            $output->writeln(sprintf('Removed %d database references to missing files.', count($missingFilePaths)));
        } catch (\Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
