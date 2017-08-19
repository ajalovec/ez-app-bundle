<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command;

use eZ\Publish\API\Repository\Repository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class ChangePasswordCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class ChangePasswordCommand extends ContainerAwareCommand
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
//        $this
//            ->setName('origammi:ez:user:change-password')
//            ->setDescription('Install project content schema.')
//            ->addOption('list-groups', null, InputOption::VALUE_NONE, 'Use this flag to force installation.')
//        ;

        $this
            ->setName('origammi:ez:user:change-password')
            ->setDescription('Change the password of a user.')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('password', InputArgument::REQUIRED, 'The password'),
            ))
            ->setHelp(<<<'EOT'
The <info>fos:user:change-password</info> command changes the password of a user:
  <info>php %command.full_name% matthieu</info>
This interactive shell will first ask you for a password.
You can alternatively specify the password as a second argument:
  <info>php %command.full_name% matthieu mypassword</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->repository = $this->getContainer()->get('ezpublish.api.repository');
        $this->username   = $input->getArgument('username');
        $this->password   = $input->getArgument('password');

        #TODO: update user

        $output->writeln(sprintf('Changed password for user <comment>%s</comment>', $this->username));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = array();
        if (!$input->getArgument('username')) {
            $question = new Question('Please give the username:');
            $question->setValidator(function ($username) {
                if (empty($username)) {
                    throw new \Exception('Username can not be empty');
                }

                return $username;
            });
            $questions['username'] = $question;
        }
        if (!$input->getArgument('password')) {
            $question = new Question('Please enter the new password:');
            $question->setValidator(function ($password) {
                if (empty($password)) {
                    throw new \Exception('Password can not be empty');
                }

                return $password;
            });
            $question->setHidden(true);
            $questions['password'] = $question;
        }
        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }
}
