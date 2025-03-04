<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All rights reserved
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

namespace Tuleap\Git\Gitolite;

use EventManager;
use ForgeConfig;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigPermissionsSerializerGitoliteConfTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    public function setUp(): void
    {
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
    }

    public function testItDumpsTheConf(): void
    {
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            $this->createMock(FineGrainedRetriever::class),
            $this->createMock(FineGrainedPermissionFactory::class),
            $this->createMock(RegexpFineGrainedRetriever::class),
            $this->createMock(EventManager::class)
        );

        self::assertSame(
            file_get_contents(__DIR__ . '/_fixtures/default_gitolite.conf'),
            $serializer->getGitoliteDotConf(['projecta', 'projectb'])
        );
    }

    public function testItAllowsOverrideBySiteAdmin(): void
    {
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
            __DIR__ . '/_fixtures/etc_templates',
            $this->createMock(FineGrainedRetriever::class),
            $this->createMock(FineGrainedPermissionFactory::class),
            $this->createMock(RegexpFineGrainedRetriever::class),
            $this->createMock(EventManager::class)
        );

        self::assertSame(
            file_get_contents(__DIR__ . '/_fixtures/override_gitolite.conf'),
            $serializer->getGitoliteDotConf(['projecta', 'projectb'])
        );
    }
}
