<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class CreateUserCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class CreateUserCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('origammi:ez:user:create')
            ->setDescription('Create new user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username')
            ->addArgument('email', InputArgument::REQUIRED, 'The email')
            ->addArgument('password', InputArgument::REQUIRED, 'The password')
            ->addArgument('groups', InputArgument::REQUIRED, 'Add user to a group.')
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
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $email    = $input->getArgument('email');
        $password = $input->getArgument('password');
        $groups   = explode(',', $input->getArgument('groups'));

        #TODO: create user

        $output->writeln(sprintf('Created user <comment>%s</comment>', $username));
        $output->writeln(sprintf('Password: <comment>%s</comment>', $password));
        $output->writeln(sprintf('Email: <comment>%s</comment>', $email));
        $output->writeln(sprintf('Groups: <comment>%s</comment>', implode('|', $groups)));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = array();
        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username:');
            $question->setValidator(function ($username) {
                if (empty($username)) {
                    throw new \Exception('Username can not be empty');
                }

                return $username;
            });
            $questions['username'] = $question;
        }
        if (!$input->getArgument('email')) {
            $question = new Question('Please choose an email:');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new \Exception('Email can not be empty');
                }

                return $email;
            });
            $question->setHidden(true);
            $questions['email'] = $question;
        }
        if (!$input->getArgument('password')) {
            $question = new Question('Please choose a password:');
            $question->setValidator(function ($password) {
                if (empty($password)) {
                    throw new \Exception('Password can not be empty');
                }

                return $password;
            });
            $questions['password'] = $question;
        }
        if (!$input->getArgument('groups')) {
            #TODO: load available user groups
            $question = new Question('Please choose user groups:');
            $question->setValidator(function ($password) {
                if (empty($password)) {
                    throw new \Exception('Groups can not be empty');
                }

                return $password;
            });
            $questions['groups'] = $question;
        }
        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }
}
