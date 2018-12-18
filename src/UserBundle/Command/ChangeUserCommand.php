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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use UserBundle\Entity\User;

/**
 * Change selected user
 * @package UserBundle\Command
 * 
 * @author Christophe Daloz - De Los Rios
 * @version 1.0
 */
class ChangeUserCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:user:change';

    protected function configure()
    {
        $this->setDescription('Change a user')
            ->setHelp('This command allows you to change a user')
            ->addArgument('username', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $persist = false;

        $output->writeln([
            'Change user',
            '-----------',
        ]);

        $username = $input->getArgument('username');
        $io = new SymfonyStyle($input, $output);

        $repos = $this->getContainer()->get('doctrine')->getRepository(User::class);
        $user = $repos->findOneBy(['username' => $username]);

        if (is_null($user)) {
            $io->error('The selected user not exist');

            return;
        }

        $io->note('You will change the '.$username.' user');

        $question = new Question("\n<info>Enter the new password (empty for not changed)</info>: \n");
        $password = trim($helper->ask($input, $output, $question));

        $question = new Question("\n<info>Enter the new mail (empty for not changed):</info> \n");
        $mail = trim($helper->ask($input, $output, $question));

        $question = new Question("\n<info>Enter the new roles (empty for not changed, pipe for separation):</info> \n");
        $roles = trim($helper->ask($input, $output, $question));

        $question = new Question("\n<info>Enter the new validation code (empty for not changed):</info> \n");
        $validationCode = trim($helper->ask($input, $output, $question));

        if (!empty($password)) {
            var_dump('test');
            $user->setPassword($this->getContainer()->get('security.password_encoder')->encodePassword($user, $password));
            $persist = true;
        }

        if (!empty($mail)) {
            $user->setMail($mail);
            $persist = true;
        }

        if (!empty($roles)) {
            $user->setRoles(explode('|', $roles));
            $persist = true;
        }

        if (!empty($validationCode)) {
            $user->setValidationCode($validationCode);
            $persist = true;
        }

        if ($persist) {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $em->persist($user);
            $em->flush();

            $io->success('This user has been changed');
        } else {
            $io->note('No change on the user');
        }
    }
}
