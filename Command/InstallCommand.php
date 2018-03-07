<?php

namespace Origammi\Bundle\EzAppBundle\Command;

use Kaliop\eZMigrationBundle\API\Collection\MigrationCollection;
use Kaliop\eZMigrationBundle\API\Value\Migration;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;


/**
 * Class InstallCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2018 Origammi (http://origammi.co)
 */
class InstallCommand extends ContainerAwareCommand
{
    /**
     * This name has to be underscored because when we are searching for file pattern in parent folder and the filename has same name this file will be sorted first.
     */
    const SEED_DIR_NAME = '_seed';

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var MigrationCollection
     */
    private $migrationsCollection;


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('origammi:ezapp:install')
            ->setDescription(sprintf("Install project content schema and demo data.\nThis command looks into directory defined by --path option and looks for all files which matches pattern [^[0-9]{3}_[A-z_0-9\-]+\.(yml|php)] and sorts them out by file name.\nIf you specify --seed option then only files from `./%s` sub-folder will be loaded.", self::SEED_DIR_NAME))
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'The directory to load the seed data from.', 'src/AppBundle/Installer')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this flag to force installation.')
            ->addOption('lang', null, InputOption::VALUE_OPTIONAL, 'Set default language code.', 'eng-GB')
            ->addOption('seed', null, InputOption::VALUE_NONE, 'Load only seed data?')
        ;
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io         = new SymfonyStyle($input, $output);
        $migrationService = $this->getMigrationsService();

        $installerPath  = $input->getOption('path');
        if (!file_exists($installerPath) || !is_dir($installerPath)) {
            throw new \LogicException(sprintf('Directory `%s` for migrations does not exist or is not directory!', $installerPath));
        }

        if ($input->getOption('seed')) {
            $installerPath .= '/' . self::SEED_DIR_NAME;
            if (!file_exists($installerPath) || !is_dir($installerPath)) {
                throw new \LogicException(sprintf('Directory `%s` does not exist or is not directory! Either remove option --seed or create directory.', $installerPath));
            }
        }

        $definitions = $this->getMigrationDefinitions($installerPath);
        if ($input->getOption('force')) {
            foreach ($definitions as $definition) {
                $migrationService->executeMigration($definition, true, $input->getOption('lang'));
            }
        }
    }


    /**
     * Get migration definitions from php and yml files under a directory
     *
     * @param string $path
     *
     * @throws \Exception
     * @return MigrationDefinition[]
     */
    protected function getMigrationDefinitions($path)
    {
        $path             = realpath($path);
        $migrationService = $this->getMigrationsService();
        $definitions      = [];
        $data             = [];
        $i                = 0;

        /** @var SplFileInfo $file */
        foreach ($this->findFiles($path) as $file) {
            if ($file->isFile()) {
                $name                = sprintf('%s_%s', $file->getRelativePath(), $file->getFilename());
                $migrationDefinition = new MigrationDefinition(
                    $name,
                    $file->getRealPath(),
                    $file->getContents()
                );
                $migrationDefinition = $migrationService->parseMigrationDefinition($migrationDefinition);

                if ($migrationDefinition->status != MigrationDefinition::STATUS_PARSED) {
                    $notes = '<error>' . $migrationDefinition->parsingError . '</error>';
                } else {
                    $migration = $this->getMigration($migrationDefinition->name);
                    if (is_null($migration) || $migration->status === Migration::STATUS_TODO) {
                        $definitions[] = $migrationDefinition;
                        $notes              = 'Ok';
                    } else {
                        switch ($migration->status) {
                            case Migration::STATUS_FAILED:
                                $notes              = '<error>Failed</error>';
                                $definitions[] = $migrationDefinition;
                                break;
                            case Migration::STATUS_PARTIALLY_DONE:
                                $notes = '<error>Partially done</error>';
                                break;
                            case Migration::STATUS_SKIPPED:
                                $notes = '<comment>Skipped</comment>';
                                break;
                            case Migration::STATUS_STARTED:
                                $notes = '<error>Started</error>';
                                break;
                            default:
                                $notes = 'Executed';
                        }
                    }
                }

                if ($file->getRelativePath() === self::SEED_DIR_NAME) {
                    $name = $file->getFilename() . ' <comment>(seed)</comment>';
                } else {
                    $name = sprintf('%s (%s)', $file->getFilename(), $file->getRelativePath());
                }
                $data[] = array(
                    $i++,
                    $name,
                    $notes,
                );
            }
        }

        $this->io->title($path);
        $this->io->table([ '#', 'Files', 'Notes' ], $data);

        return $definitions;
    }


    /**
     * The kaliop migrations service
     * @return \Kaliop\eZMigrationBundle\Core\MigrationService
     */
    protected function getMigrationsService()
    {
        return $this->getContainer()->get('ez_migration_bundle.migration_service');
    }


    /**
     * Get a migration from the db
     *
     * @param string $name
     *
     * @return Migration
     */
    protected function getMigration($name)
    {
        if (is_null($this->migrationsCollection)) {
            $this->migrationsCollection = $this->getMigrationsService()->getMigrations();
        }

        return isset($this->migrationsCollection[$name]) ? $this->migrationsCollection[$name] : null;
    }


    /**
     * Find files in path
     *
     * @param string $path
     *
     * @return Finder
     */
    private function findFiles($path)
    {
        $files = Finder::create()
            ->in($path)
            ->name('/^[0-9]{3}_[A-z_0-9\-]+\.(yml|php)/')
            ->files()
            ->sort(function (SplFileInfo $f1, SplFileInfo $f2) {
                // modify file name so we add relative path between sorting numbers and name of the file so we get same sorting as directories are sorted
                // Example: _seed/010_my_migration.yml > 010__seed_my_migration.yml
                // Example: demo/013_my_migration.yml > 013_demo_my_migration.yml
                $f1Name = sprintf('%s_%s_%s', substr($f1->getFilename(), 0, 3), $f1->getRelativePath(), substr($f1->getFilename(), 4));
                $f2Name = sprintf('%s_%s_%s', substr($f2->getFilename(), 0, 3), $f2->getRelativePath(), substr($f2->getFilename(), 4));

                return strcmp($f1Name, $f2Name);
            })
        ;

        return $files;
    }
}
