<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\Profile;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\User\Avatar\AvatarHashStorageDeletor;
use UserDao;
use UserManager;

final class ForceRegenerationDefaultAvatarCommand extends Command
{
    public const NAME = 'user:force-regeneration-default-avatar';

    public function __construct(
        private readonly UserManager $user_manager,
        private readonly UserDao $user_dao,
        private readonly AvatarHashStorageDeletor $avatar_hash_storage,
    ) {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Remove all the generated default avatars to force their regeneration.');
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $user_with_default_avatar_rows = $this->user_dao->searchUsersWithDefaultAvatar();

        $progress_bar = new ProgressBar($output);

        foreach ($progress_bar->iterate($user_with_default_avatar_rows) as $user_row) {
            $user             = $this->user_manager->getUserInstanceFromRow($user_row);
            $user_avatar_path = $user->getAvatarFilePath();

            if (is_file($user_avatar_path)) {
                unlink($user_avatar_path);
            }
            $this->avatar_hash_storage->delete($user);
        }

        $output->writeln('');

        return 0;
    }
}
