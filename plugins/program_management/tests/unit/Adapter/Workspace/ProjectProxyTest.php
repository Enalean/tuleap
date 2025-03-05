<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectProxyTest extends TestCase
{
    public function testItBuildsPrimitive(): void
    {
        $project = new \Project(['group_id' => 101, 'group_name' => 'My project', 'unix_group_name' => 'project', 'icon_codepoint' => '"\u26f0\ufe0f"']);
        $proxy   = ProjectProxy::buildFromProject($project);

        self::assertEquals($project->getID(), $proxy->getId());
        self::assertEquals($project->getPublicName(), $proxy->getProjectLabel());
        self::assertEquals($project->getUnixNameLowerCase(), $proxy->getProjectShortName());
        self::assertEquals($project->getUrl(), $proxy->getUrl());
        self::assertEquals(EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat('"\u26f0\ufe0f"'), $proxy->getProjectIcon());
    }
}
