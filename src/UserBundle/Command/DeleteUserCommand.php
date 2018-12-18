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
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use UserBundle\Entity\User;


/**
 * Delete selected user
 * @package UserBundle\Command
 *
 * @author Christophe Daloz - De Los Rios
 * @version 1.0
 */
class DeleteUserCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:user:delete';

    protected function configure()
    {
        $this->setDescription('Delete a user')
            ->setHelp('This command allows you to delete a user')
            ->addArgument('username', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $persist = false;

        $output->writeln([
            'Delete user',
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

        $question = new ChoiceQuestion("\n<info>Do you really want to delete the user?</info> \n", ['yes','no'], 1);
        $question->setErrorMessage('Invalid choice');
        $confirmation = trim($helper->ask($input, $output, $question));

        if ($confirmation === 'no') {
            $io->note('The selected user has not delete, process stop by user');
            return;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($user);
        $em->flush();

        $io->success('The selected user has been delete');
    }
}
