<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

require_once __DIR__ . '/DatabaseInitialization.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class PullRequestDataBuilder extends REST_TestDataBuilder
{
    public function setUp(): void
    {
        PluginManager::instance()->installAndEnable('pullrequest');
        $this->initDatabase();
    }

    private function initDatabase(): void
    {
        $initializer = new Tuleap\PullRequest\REST\DatabaseInitialization();
        $initializer->setUp();
    }
}
