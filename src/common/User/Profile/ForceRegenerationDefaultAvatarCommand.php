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
use UserDao;
use UserManager;

final class ForceRegenerationDefaultAvatarCommand extends Command
{
    public const NAME = 'user:force-regeneration-default-avatar';

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserDao
     */
    private $user_dao;

    public function __construct(UserManager $user_manager, UserDao $user_dao)
    {
        parent::__construct(self::NAME);

        $this->user_manager = $user_manager;
        $this->user_dao     = $user_dao;
    }

    protected function configure(): void
    {
        $this->setDescription('Remove all the generated default avatars to force their regeneration.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $user_with_default_avatar_rows = $this->user_dao->searchUsersWithDefaultAvatar();
        if ($user_with_default_avatar_rows === false) {
            throw new \RuntimeException('Cannot search users with default avatar');
        }

        $progress_bar = new ProgressBar($output);

        foreach ($progress_bar->iterate($user_with_default_avatar_rows) as $user_row) {
            $user             = $this->user_manager->getUserInstanceFromRow($user_row);
            $user_avatar_path = $user->getAvatarFilePath();

            if (is_file($user_avatar_path)) {
                unlink($user_avatar_path);
            }
        }

        $output->writeln('');

        return 0;
    }
}
