<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class CreateUserCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class CreateUserCommand extends BaseCommand
{
    const COMMAND_NAME = 'origammi:ez:user:create';


    /**
     * Configure command.
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Create new user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username')
            ->addArgument('email', InputArgument::REQUIRED, 'The email')
            ->addArgument('password', InputArgument::REQUIRED, 'The password')
            ->addArgument('groups', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Add user to a group.')
            ->setHelp(<<<'EOT'
The <info>fos:user:create</info> command creates a user:
  <info>php %command.full_name% acme</info>
This interactive shell will ask you for an email and then a password.
You can alternatively specify the email and password as the second and third arguments:
  <info>php %command.full_name% acme acme@email.com mypassword</info>
You can assign user to one or more groups (separate ids with ,):
  <info>php %command.full_name% acme acme@email.com mypassword 3</info>
  <info>php %command.full_name% acme acme@email.com mypassword 5,33,44,55,66</info>
EOT
            )
        ;
    }

    /**
     * Configure command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user       = [];
        $userGroups = $input->getArgument('groups');

        $user['username']   = $input->getArgument('username');
        $user['email']      = $input->getArgument('email');
        $user['password']   = $input->getArgument('password');
        $user['first_name'] = $user['last_name'] = $user['username'];

        $this->getUserManager()->create($user, $userGroups, 'eng-GB');

        $output->writeln(sprintf('Created user <comment>%s</comment>', $user['username']));
        return;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = array();

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username:');
            $question->setValidator($this->getIsEmptyValidator('Username can not be empty'));
            $questions['username'] = $question;
        }

        if (!$input->getArgument('email')) {
            $question = new Question('Please choose an email:');
            $question->setValidator($this->getIsEmptyValidator('Email can not be empty'));
            $question->setHidden(true);
            $questions['email'] = $question;
        }

        if (!$input->getArgument('password')) {
            $question = new Question('Please choose a password:');
            $question->setValidator($this->getIsEmptyValidator('Password can not be empty'));
            $questions['password'] = $question;
        }

        if (!$input->getArgument('groups')) {
            $api = $this->getContainer()->get('origammi_ezapp.api');

            $usersRootLocation = $api->loadLocation(5);
            $userGroups        = $api->getLocationService()->findBySubtree($usersRootLocation, ['user_group']);
            $groups            = [];

            foreach ($userGroups as $userGroup) {
                $groups[$userGroup->contentId] = $userGroup->contentInfo->name;
            }

            $question = new ChoiceQuestion('Please choose user groups:', $groups);
            $question
                ->setMultiselect(true)
                ->setValidator($this->getChoicesValidator($groups))
            ;
            $questions['groups'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);

            $input->setArgument($name, $answer);
        }
    }

    /**
     * @param string $errorMessage
     *
     * @return \Closure
     */
    private function getIsEmptyValidator($errorMessage = 'Value can not be empty')
    {
        return function ($value) use ($errorMessage) {
            if (empty($value)) {
                throw new \Exception($errorMessage);
            }

            return $value;
        };
    }

    /**
     * @param string $errorMessage
     *
     * @return \Closure
     */
    private function getChoicesValidator(array $choices, $errorMessage = 'Value can not be empty')
    {
        return function ($selected) use ($choices, $errorMessage) {
            if (!preg_match('/^[^,]+(?:,[^,]+)*$/', $selected, $matches)) {
                throw new \InvalidArgumentException(sprintf($errorMessage, $selected));
            }
            $selectedChoices = explode(',', $selected);

            $multiselectChoices = array();
            foreach ($selectedChoices as $value) {
                if (!isset($choices[$value])) {
                    throw new \InvalidArgumentException(sprintf('The provided answer is ambiguous. Value should be one of %s.', implode(' or ', array_keys($choices))));
                }

                $multiselectChoices[] = $value;
            }

            return $multiselectChoices;
        };
    }
}
