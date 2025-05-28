<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\UserAccount;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\OpenIDConnectClient\UserMapping\RemoveUserMappingDao;
use UserManager;

final class UnlinkCommand extends Command
{
    public const NAME = 'oidc_client:remove-all-user-links';

    public function __construct(
        private readonly UserManager $user_manager,
        private readonly RemoveUserMappingDao $dao,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        // This command is hidden because it is only here for testing purpose
        // We don't want to remove all providers for a user in a production environment
        // because the user will not be able to login again since they don't have any password
        // This would introduce weakness in authentication mechanism
        $this
            ->setHidden()
            ->setDescription('Unlink user from all OIDC providers')
            ->addArgument(
                'user_name',
                InputArgument::REQUIRED,
                'Tuleap username'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $user_name = $input->getArgument('user_name');
        $user      = $this->user_manager->getUserByLoginName($user_name);
        if ($user === null) {
            $output->writeln('<error>User ' . OutputFormatter::escape($user_name) . ' can not be found</error>');
            return self::FAILURE;
        }

        $nb_affected = $this->dao->deleteByUserId((int) $user->getId());
        $message     = match ($nb_affected) {
            0 => 'User ' . OutputFormatter::escape($user_name) . ' is already not linked to any provider',
            1 => 'User ' . OutputFormatter::escape($user_name) . ' has been unlinked from one provider',
            default => sprintf(
                'User ' . OutputFormatter::escape($user_name) . ' has been unlinked from %d providers',
                $nb_affected,
            ),
        };
        $output->writeln('<info>' . $message . '</info>');
        return self::SUCCESS;
    }
}
