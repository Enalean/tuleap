<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Docman\Settings\SettingsDAO;
use Tuleap\Docman\Test\rest\Helper\DocmanProjectBuilder;
use Tuleap\REST\RESTTestDataBuilder;

class DocmanForbidWritersDataBuilder
{
    public const PROJECT_NAME     = 'docman-forbid-writers';
    public const WRITER_USERNAME  = 'docman_writer_user';
    public const MANAGER_USERNAME = 'docman_manager_user';

    public function __construct(
        private DocmanProjectBuilder $project_builder,
        private \UserManager $user_manager,
        private SettingsDAO $settings_dao,
        private \ProjectManager $project_manager,
    ) {
    }

    public function setUp(): void
    {
        echo 'Setup Docman REST Tests configuration for ForbidWriters' . PHP_EOL;

        $this->changePasswordForUser(self::WRITER_USERNAME);
        $this->changePasswordForUser(self::MANAGER_USERNAME);

        $this->forbidWritersInProjectSettings();
    }

    private function changePasswordForUser(string $name): void
    {
        $user = $this->user_manager->getUserByUserName($name);
        $user->setPassword(new ConcealedString(RESTTestDataBuilder::STANDARD_PASSWORD));
        $this->user_manager->updateDb($user);
    }

    private function forbidWritersInProjectSettings(): void
    {
        $this->settings_dao->saveForbidWriters(
            (int) $this->project_manager->getProjectByUnixName(self::PROJECT_NAME)->getID(),
            true,
            true,
        );
    }
}
