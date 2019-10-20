<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media\Unused;

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
        $this->setName('catalog:media:unused:remove')
            ->setDescription(
                'Remove all product media images which exist on the filesystem but are not in use.'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $unusedFilePaths = $this->getUnusedFilePaths();
            $this->deleteFilePaths($unusedFilePaths);
            $output->writeln(sprintf('Removed %d unused files.', count($unusedFilePaths)));
        } catch (\Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
