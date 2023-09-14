<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command\Catalog\Media;

use MagentoCode\CliMediaTool\Command\CatalogAbstract;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Temporary extends CatalogAbstract
{
    public const OPTION_MAX_LIFETIME = 'max_lifetime';
    public const DEFAULT_MAX_LIFETIME = 24;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('catalog:media:temporary')
            ->addOption(
                self::OPTION_MAX_LIFETIME,
                'm',
                InputArgument::OPTIONAL,
                'Max temporary file lifetime in hours?',
                self::DEFAULT_MAX_LIFETIME
            )
            ->setDescription(
                'Get a list of product media temporary images which exist on the filesystem.'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $maxLifetimeInHours = (int)$input->getOption(self::OPTION_MAX_LIFETIME);
            $temporaryFiles = $this->getTemporaryFilePaths(true, $maxLifetimeInHours);
            $output->writeln($temporaryFiles);
        } catch (\Exception $exception) {
            $output->writeln(['Exception: ', $exception->getMessage()]);
        }
    }
}
