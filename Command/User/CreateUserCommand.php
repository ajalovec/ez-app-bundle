<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addArgument('groups', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'One or more user group ids (separated with `,) <comment>[example: 22 44 24 63]</comment>')
            ->addOption('disabled', null, InputOption::VALUE_NONE, 'Disable the user by default')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Set the user as admin')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command creates new user:

  <info>php %command.full_name% acme</info>
  
This interactive shell will ask you for more required arguments.

You can alternatively specify more arguments when writing the command name:

  <info>%command.full_name% acme acme@email.com</info>
  
  <info>%command.full_name% acme acme@email.com 1234</info>
  
You have to specify one or more user groups or use --admin option to assign user to administrator group:

  <info>%command.full_name% acme acme@email.com 1234 3</info>
  
  <info>%command.full_name% acme acme@email.com 1234 5 33 44 55 66</info>
  
  <info>%command.full_name% acme acme@email.com 1234 --admin</info>

You can create disabled user (will not be able to log in):

  <info>%command.full_name% acme acme@email.com 1234 --disabled</info>

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
     * @throws \Exception
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

        $this->getUserManager()->create($user, $userGroups);

        $output->writeln(sprintf('Created user <comment>%s</comment>', $user['username']));
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

//        if (!$input->getArgument('groups')) {
//            $questions['groups'] = $this->createUserGroupsChoiceQuestion();
//        }

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
}
