<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
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
use Git_Mirror_Mirror;
use Mockery;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class ConfigPermissionsSerializerGitoliteConfTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $mirror_mapper;
    private $mirror_1;
    private $mirror_2;

    public function setUp() : void
    {
        parent::setUp();
        $this->mirror_mapper = Mockery::spy(\Git_Mirror_MirrorDataMapper::class);

        $user_mirror1 = Mockery::spy(\PFUser::class);
        $user_mirror1->shouldReceive('getUserName')->andReturn('forge__gitmirror_1');
        $this->mirror_1 = new Git_Mirror_Mirror($user_mirror1, 1, 'url', 'hostname', 'CHN');

        $user_mirror2 = Mockery::spy(\PFUser::class);
        $user_mirror2->shouldReceive('getUserName')->andReturn('forge__gitmirror_2');
        $this->mirror_2 = new Git_Mirror_Mirror($user_mirror2, 2, 'url', 'hostname', 'JPN');

        $this->cache_dir = trim(`mktemp -d -p /var/tmp cache_dir_XXXXXX`);
        \ForgeConfig::store();
        \ForgeConfig::set('codendi_cache_dir', $this->cache_dir);
    }

    protected function tearDown() : void
    {
        exec('rm -rf ' . escapeshellarg($this->cache_dir));
        \ForgeConfig::restore();
        parent::tearDown();
    }

    public function testItDumpsTheConf()
    {
        $this->mirror_mapper->shouldReceive('fetchAll')->andReturn(array());
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_mapper,
            Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            Mockery::spy(EventManager::class)
        );

        $this->assertSame(
            file_get_contents(__DIR__ . '/_fixtures/default_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }

    public function testItAllowsOverrideBySiteAdmin()
    {
        $this->mirror_mapper->shouldReceive('fetchAll')->andReturn(array());
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_mapper,
            Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            __DIR__ . '/_fixtures/etc_templates',
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            Mockery::spy(EventManager::class)
        );

        $this->assertSame(
            file_get_contents(__DIR__ . '/_fixtures/override_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }

    public function testItGrantsReadAccessToGitoliteAdminForMirrorUsers()
    {
        $this->mirror_mapper->shouldReceive('fetchAll')->andReturn(array($this->mirror_1, $this->mirror_2));
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_mapper,
            Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            Mockery::spy(EventManager::class)
        );
        $this->assertSame(
            file_get_contents(dirname(__FILE__) . '/_fixtures/mirrors_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }
}
