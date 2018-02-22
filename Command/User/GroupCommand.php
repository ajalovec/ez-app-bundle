<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\User;

use eZ\Publish\API\Repository\UserService;
use Origammi\Bundle\EzAppBundle\Manager\UserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class GroupCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
abstract class GroupCommand extends BaseCommand
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $definition = $this->getDefinition();
        $arguments  = $definition->getArguments();
        $definition->setArguments();

        $definition->addArgument(new InputArgument(
            'username', InputArgument::REQUIRED,
            'The username'
        ));

        $definition->addArguments($arguments);

        $definition->addArgument(new InputArgument(
            'groups', InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'One or more user group ids <comment>[example: 22 44 24 63]</comment>'
        ));

        $definition->addOption(new InputOption('admin', null, InputOption::VALUE_NONE, 'Auto administrator group assign'));
    }


    public function getHelp()
    {
        $help = <<<'EOT'
The <info>%command.name%</info> command %command.description%

By default, the command interacts with the developer for easier usage.
Any passed options will be used and interaction will be skipped.

    <info>%command.full_name% acme</info>
  
Define user group:
    
    <info>%command.full_name% %command.arguments% 44</info>

You can also define multiple user groups:

    <info>%command.full_name% %command.arguments% 5 33 44 55 66</info>

Use <comment>--admin</comment> option to define administrator user group:

    <info>%command.full_name% acme --admin</info>
%command.help%
EOT;

        $args = $this->getDefinition()->getArguments();
        array_pop($args);

        $args = array_map(function (InputArgument $v) {
            return "[{$v->getName()}]";
        }, $args);
        $args = implode(' ', $args);

        return self::replace_text([
            '%command.description%' => lcfirst((string)$this->getDescription()),
            '%command.help%'        => (string)parent::getHelp(),
            '%command.arguments%'   => $args,
        ],
            $help
        );
    }


    /**
     * @param UserManager|UserService $manager
     * @param OutputStyle             $output
     * @param string                  $username
     * @param array                   $groups
     * @param bool                    $hasAdminGroup
     */
    abstract protected function executeGroupCommand(UserManager $manager, OutputStyle $output, $username, array $groups, $hasAdminGroup);


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $groups   = $input->getArgument('groups');
        $admin    = $input->getOption('admin');

        if (true === $admin) {
            if (empty($groups)) {
                $groups = [ $this->adminUserId ];
            } else {
                throw new \InvalidArgumentException('You can pass either the groups or the --admin option (but not both simultaneously).');
            }
        }

        if (empty($groups)) {
            throw new \InvalidArgumentException('You need to pass at least 1 argument, either the groups or the --admin option.');
        }

        if (false !== array_search($this->adminUserId, $groups)) {
            $admin = true;
        }

        $manager = $this->getContainer()->get('origammi.ez_app.manager.user');
        $manager->loginAdminUser();
        $outputHelper = new SymfonyStyle($input, $output);
        $this->executeGroupCommand($manager, $outputHelper, $username, $groups, $admin);
    }


    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $outputHelper = new SymfonyStyle($input, $output);

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username:');
            $question->setValidator(function ($value) {
                $this->getUserManager()->loadUserByLogin($value);

                return $value;
            });

            $input->setArgument('username', $outputHelper->askQuestion($question));
        }

        if (true !== $input->getOption('admin') && !$input->getArgument('groups')) {
            $choices  = $this->loadUserGroupChoices();
            $question = new ChoiceQuestion('Please choose user groups:', array_flip($choices));
            $question->setMultiselect(true);

            $answer = $outputHelper->askQuestion($question);
            foreach ($answer as &$item) {
                $item = $choices[$item];
            }
            $input->setArgument('groups', $answer);
        }
    }

    private function loadUserGroupChoices()
    {
        $api = $this->getContainer()->get('origammi.ez_app.repository.api');

        $loadedGroups = $api->getLocationService()->findByContentType('user_group');
        $choices      = [];
        foreach ($loadedGroups as $userGroup) {
            $choices[$userGroup->contentInfo->name] = $userGroup->contentId;
        }

        return $choices;
    }

    private function replace_text(array $searchMap, $text)
    {
        return str_replace(array_keys($searchMap), $searchMap, $text);
    }
}
