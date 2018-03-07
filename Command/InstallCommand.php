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
            ->setDescription('Install project content schema.')
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'The directory to load the seed data from.', 'src/AppBundle/Installer')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this flag to force installation.')
            ->addOption('lang', null, InputOption::VALUE_OPTIONAL, 'Set default language code.', 'eng-GB')
            ->addOption('demo', null, InputOption::VALUE_NONE, 'Load demo data?')
        ;
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io         = new SymfonyStyle($input, $output);
        $migrationService = $this->getMigrationsService();

        $seedPath = $input->getOption('path') . '/seed';
        if (!file_exists($seedPath) || !is_dir($seedPath)) {
            throw new \LogicException(sprintf('Directory `%s` for migrations does not exist or is not directory!', $seedPath));
        }

        $definitions = $this->getMigrationDefinitions($seedPath);

        $demoPath = $input->getOption('path') . '/demo';
        if ($input->getOption('demo')) {
            if (!file_exists($demoPath) || !is_dir($demoPath)) {
                throw new \LogicException(sprintf('Directory `%s` does not exist or is not directory! Either remove option --demo or create directory.', $demoPath));
            }
            $definitions = array_merge($definitions, $this->getMigrationDefinitions($demoPath));
        }

        if ($input->getOption('force')) {
            foreach ($definitions as $filename => $definition) {
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
                $name                = $file->getFilename();
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
                        $definitions[$name] = $migrationDefinition;
                        $notes              = 'Ok';
                    } else {
                        switch ($migration->status) {
                            case Migration::STATUS_FAILED:
                                $notes              = '<error>Failed</error>';
                                $definitions[$name] = $migrationDefinition;
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
            ->sort(function (\SplFileInfo $f1, \SplFileInfo $f2) {
                return strcmp($f1->getFilename(), $f2->getFilename());
            })
        ;

        return $files;
    }
}
