<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media\Cache;

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
        $this->setName('catalog:media:cache:remove')
            ->setDescription(
                'Remove all product media cache images which exist on the filesystem.'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $cacheFilePaths = $this->getCacheFilePaths();
            $this->deleteFilePaths($cacheFilePaths);
            $output->writeln(sprintf('Removed %d cache files.', count($cacheFilePaths)));
        } catch (\Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
