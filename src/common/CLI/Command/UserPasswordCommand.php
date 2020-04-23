<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Password\PasswordSanityChecker;
use UserManager;

class UserPasswordCommand extends Command
{
    public const NAME = 'set-user-password';

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var PasswordSanityChecker
     */
    private $password_sanity_checker;

    public function __construct(UserManager $user_manager, PasswordSanityChecker $password_sanity_checker)
    {
        parent::__construct(self::NAME);

        $this->user_manager            = $user_manager;
        $this->password_sanity_checker = $password_sanity_checker;
    }

    protected function configure()
    {
        $this->setDescription('Set a user password')
            ->addArgument('user_name', InputArgument::REQUIRED, 'Tuleap username')
            ->addArgument('password', InputArgument::OPTIONAL, 'New password for user');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $user_name = $input->getArgument('user_name');
        $user      = $this->user_manager->getUserByLoginName($user_name);

        if (! $user) {
            throw new InvalidArgumentException("User $user_name not found.");
        }

        $password_cleartext = $input->getArgument('password');
        if (! $password_cleartext) {
            $helper = $this->getHelper('question');

            $question = new Question('New password? ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);

            $password_cleartext = $helper->ask($input, $output, $question);
        }
        assert(is_string($password_cleartext));
        $password = new ConcealedString($password_cleartext);
        sodium_memzero($password_cleartext);

        if (! $this->password_sanity_checker->check($password)) {
            throw new InvalidArgumentException("The provided password does not match the expected password policy.");
        }

        $user->setPassword($password);
        if (! $this->user_manager->updateDb($user)) {
            return 1;
        }

        return 0;
    }
}
