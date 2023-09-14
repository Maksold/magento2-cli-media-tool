<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media\Temporary;

use Exception;
use MagentoCode\CliMediaTool\Command\Catalog\Media\Temporary;
use MagentoCode\CliMediaTool\Command\CatalogAbstract;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Remove extends CatalogAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('catalog:media:temporary:remove')
            ->addOption(
                Temporary::OPTION_MAX_LIFETIME,
                'm',
                InputArgument::OPTIONAL,
                'Max temporary file lifetime in hours?',
                Temporary::DEFAULT_MAX_LIFETIME
            )
            ->setDescription(
                'Remove all temporary product media images which exist on the filesystem.'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $maxLifetimeInHours = (int)$input->getOption(Temporary::OPTION_MAX_LIFETIME);
            $temporaryFilePaths = $this->getTemporaryFilePaths(true, $maxLifetimeInHours);
            $this->deleteFilePaths($temporaryFilePaths);
            $output->writeln(sprintf(
                'Removed %d temporary files older that %d hour(s).',
                count($temporaryFilePaths),
                $maxLifetimeInHours
            ));
        } catch (Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
