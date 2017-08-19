<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class InstallCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class InstallCommand extends ContainerAwareCommand
{
    private $defaultPath;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('origammi:ezapp:install')
            ->setDescription('Install project content schema.')
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'The directory to load the seed data from.', 'src/AppBundle/Installer/seed')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Specify the number for migration limit.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this flag to force installation.')
            ->addOption('lang', null, InputOption::VALUE_OPTIONAL, 'Set default language code.', 'eng-GB')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->defaultPath = $input->getOption('path');
        $migrationService = $this->getContainer()->get('ez_migration_bundle.migration_service');
        $files            = $this->findFiles();

        $definitions = [];
        $data        = [];
        $i           = 0;
        $limit       = (int)$input->getOption('limit');

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile()) {
                $name = $file->getFilename();

                if ($limit && intval($name) > $limit) {
                    break;
                }

                $migrationDefinition = new MigrationDefinition(
                    $name,
                    $file->getRealPath(),
                    file_get_contents($file->getRealPath())
                );
                $migrationDefinition = $migrationService->parseMigrationDefinition($migrationDefinition);
                $definitions[$name]  = $migrationDefinition;

                if ($migrationDefinition->status != MigrationDefinition::STATUS_PARSED) {
                    $notes = '<error>' . $migrationDefinition->parsingError . '</error>';
                } else {
                    $notes = 'Ok';
                }

                $data[] = array(
                    $i++,
                    $name,
                    $notes,
                );
            }
        }

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('#', 'Files', 'Notes'))
            ->setRows($data)
        ;

        $table->render($output);

        if ($input->getOption('force')) {
            foreach ($definitions as $definition) {
                $migrationService->executeMigration($definition, true, $input->getOption('lang'));
            }
        }
    }

    private function findFiles()
    {
        $files = Finder::create()
            ->in($this->defaultPath)
            ->name('/^[0-9]{3}_[\w_\-\d]+\.(yml|php)/')
            ->files()
            ->sort(function (\SplFileInfo $f1, \SplFileInfo $f2) {
                return strcmp($f1->getFilename(), $f2->getFilename());
            })
        ;

        return $files;
    }
}
