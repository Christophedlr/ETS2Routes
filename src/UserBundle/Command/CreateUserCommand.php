<?php

/**
 * Copyright (C) 2018 Christophe Daloz - De Los Rios
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the “Software”), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * The Software is provided “as is”, without warranty of any kind, express or implied, including but not
 * limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
 * In no event shall the authors or copyright holders be liable for any claim, damages or other liability,
 * whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software
 * or the use or other dealings in the Software.
 */

namespace UserBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use UserBundle\Entity\User;

/**
 * Create a new user
 * @package UserBundle\Command
 *
 * @author Christophe Daloz - De Los Rios
 * @version 1.0
 */
class CreateUserCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:user:create';

    private $username;
    private $password;
    private $mail;
    private $roles;

    protected function configure()
    {
        $this->setDescription('Create à new user')
            ->setHelp('This command allows you to create a user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $output->writeln([
            'User Creator',
            '------------',
        ]);

        $question = new Question("\n<info>Please enter the username:</info>\n");
        $this->username = trim($helper->ask($input, $output, $question));

        $question = new Question("\n<info>Please enter the password:</info>\n");
        $this->password = trim($helper->ask($input, $output, $question));

        $question = new Question("\n<info>Please enter the e-mail:</info>\n");
        $this->mail = trim($helper->ask($input, $output, $question));

        $question = new Question("\n<info>Please enter the roles (pipe separation)</info>:\n");
        $this->roles = explode('|', trim($helper->ask($input, $output, $question)));

        $user = new User();
        $user->setUsername($this->username);
        $user->setPassword($this->getContainer()->get('security.password_encoder')->encodePassword($user, $this->password));
        $user->setMail($this->mail);
        $user->setRoles($this->roles);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        $io = new SymfonyStyle($input, $output);
        $io->success("The user has been created in database");
    }
}
