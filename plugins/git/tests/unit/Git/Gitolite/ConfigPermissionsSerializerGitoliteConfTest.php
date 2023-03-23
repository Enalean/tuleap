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

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git_Gitolite_ConfigPermissionsSerializer;
use Mockery;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;

class ConfigPermissionsSerializerGitoliteConfTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private string $cache_dir;

    public function setUp(): void
    {
        parent::setUp();

        $this->cache_dir = $this->getTmpDir();
        \ForgeConfig::set('codendi_cache_dir', $this->cache_dir);
    }

    public function testItDumpsTheConf(): void
    {
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            Mockery::spy(EventManager::class)
        );

        $this->assertSame(
            file_get_contents(__DIR__ . '/_fixtures/default_gitolite.conf'),
            $serializer->getGitoliteDotConf(['projecta', 'projectb'])
        );
    }

    public function testItAllowsOverrideBySiteAdmin(): void
    {
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            __DIR__ . '/_fixtures/etc_templates',
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            Mockery::spy(EventManager::class)
        );

        $this->assertSame(
            file_get_contents(__DIR__ . '/_fixtures/override_gitolite.conf'),
            $serializer->getGitoliteDotConf(['projecta', 'projectb'])
        );
    }
}
