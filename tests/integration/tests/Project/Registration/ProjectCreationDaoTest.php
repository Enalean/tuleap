<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration;

use Tuleap\DB\DBFactory;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectCreationDaoTest extends TestIntegrationTestCase
{
    public function testItReturnsCreatedProjectId(): void
    {
        $id = (new ProjectCreationDao())->create(ProjectCreationData::buildFromFormArray(
            new DefaultProjectVisibilityRetriever(),
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'form_unix_name'         => 'acme',
                    'form_full_name'         => 'Acme Project',
                    'form_short_description' => "That's all folks!",
                    'is_public'              => '0',
                    'allow_restricted'       => '1',

                ],
            ],
        ));

        $row = DBFactory::getMainTuleapDBConnection()
            ->getDB()
            ->row('SELECT * FROM `groups` WHERE group_id = ?', $id);

        self::assertSame(ProjectCreationDao::TYPE_PROJECT, $row['type']);
        self::assertSame('acme', $row['unix_group_name']);
        self::assertSame('Acme Project', $row['group_name']);
        self::assertSame("That's all folks!", $row['short_description']);
        self::assertSame(\Project::STATUS_PENDING, $row['status']);
        self::assertSame(\Project::ACCESS_PRIVATE, $row['access']);
    }
}
