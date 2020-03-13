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
use Git;
use Git_Gitolite_ConfigPermissionsSerializer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tuleap\Git\Permissions\FineGrainedPermission;

require_once __DIR__ . '/../../bootstrap.php';

class ConfigPermissionsSerializerFineGrainedPermissionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tuleap\Git\Permissions\FineGrainedRetriever
     */
    private $retriever;

    /**
     * @var Tuleap\Git\Permissions\FineGrainedPermissionFactory
     */
    private $factory;

    /**
     * @var Git_Gitolite_ConfigPermissionsSerializer
     */
    private $serializer;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var ProjectUGroup
     */
    private $ugroup_01;

    /**
     * @var ProjectUGroup
     */
    private $ugroup_02;

    /**
     * @var ProjectUGroup
     */
    private $ugroup_03;

    /**
     * @var ProjectUGroup
     */
    private $ugroup_nobody;

    /**
     * @var FineGrainedPermission
     */
    private $permission_01;

    /**
     * @var FineGrainedPermission
     */
    private $permission_02;

    /**
     * @var FineGrainedPermission
     */
    private $permission_03;

    /**
     * @var Tuleap\Git\Permissions\RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    public function setUp() : void
    {
        parent::setUp();

        $globals = array_merge([], $GLOBALS);

        $this->retriever = Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class);
        $this->factory   = Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class);
        $this->regexp_retriever = Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class);

        $mapper = Mockery::spy(\Git_Mirror_MirrorDataMapper::class);
        $mapper->shouldReceive('fetchAllRepositoryMirrors')->andReturn([]);
        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $mapper,
            Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            $this->retriever,
            $this->factory,
            $this->regexp_retriever,
            Mockery::spy(EventManager::class)
        );

        $this->project = Mockery::spy(\Project::class);

        $this->repository = Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturn(1);
        $this->repository->shouldReceive('getProject')->andReturn($this->project);

        $this->permissions_manager = Mockery::spy(\PermissionsManager::class);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->with(Mockery::any(), Mockery::any(), Git::PERM_READ)
            ->andReturn([ProjectUGroup::REGISTERED]);

        $this->ugroup_01 = Mockery::spy(\ProjectUGroup::class);
        $this->ugroup_01->shouldReceive('getId')->andReturn('101');

        $this->ugroup_02 = Mockery::spy(\ProjectUGroup::class);
        $this->ugroup_02->shouldReceive('getId')->andReturn('102');

        $this->ugroup_03 = Mockery::spy(\ProjectUGroup::class);
        $this->ugroup_03->shouldReceive('getId')->andReturn('103');

        $this->ugroup_nobody = Mockery::spy(\ProjectUGroup::class);
        $this->ugroup_nobody->shouldReceive('getId')->andReturn('100');

        $this->permission_01 = new FineGrainedPermission(
            1,
            1,
            'refs/heads/master',
            array(),
            array()
        );

        $this->permission_02 = new FineGrainedPermission(
            2,
            1,
            'refs/tags/v1',
            array(),
            array()
        );

        $this->permission_03 = new FineGrainedPermission(
            3,
            1,
            'refs/heads/dev/*',
            array(),
            array()
        );

        PermissionsManager::setInstance($this->permissions_manager);

        $GLOBALS = $globals;
    }

    public function tearDown() : void
    {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function testItMustFollowTheExpectedOrderForPermission()
    {
        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturn(array(1 => $this->permission_01));
        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturn(array());

        $writers   = array($this->ugroup_01, $this->ugroup_02);
        $rewinders = array($this->ugroup_03);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturn(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all

EOS;

        $this->assertSame($config, $expected);
    }

    public function testItFetchesFineGrainedPermissions()
    {
        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturn(array(1 => $this->permission_01));
        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturn(array(2 => $this->permission_02));

        $writers   = array($this->ugroup_01, $this->ugroup_02);
        $rewinders = array($this->ugroup_03);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturn(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertSame($config, $expected);
    }

    public function testItDealsWithNobody()
    {
        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturn(array(1 => $this->permission_01));
        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturn(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array($this->ugroup_03);
        $rewinders_nobody = array($this->ugroup_nobody);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders_nobody);

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturn(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertSame($config, $expected);
    }

    public function testItDealsWithStarPath()
    {
        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturn(array(1 => $this->permission_03));
        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturn(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array($this->ugroup_03);

        $this->permission_03->setWriters($writers);
        $this->permission_03->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturn(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/dev/.*$ = @ug_103
 RW refs/heads/dev/.*$ = @ug_101 @ug_102
 - refs/heads/dev/.*$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertSame($config, $expected);
    }

    public function testItDealsWithNoUgroupSelected()
    {
        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturn(array(1 => $this->permission_01));
        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturn(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array();

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturn(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertSame($config, $expected);
    }

    public function testItDeniesPatternIfNobodyCanWriteAndRewind()
    {
        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturn(array(1 => $this->permission_01));
        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturn(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_nobody);
        $rewinders        = array();

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturn(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 - refs/heads/master$ = @all
 - refs/tags/v1$ = @all

EOS;

        $this->assertSame($config, $expected);
    }

    public function testItAddEndCharacterAtPatternEndWhenRegexpAreDisabled()
    {
        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturn(array(1 => $this->permission_01));
        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturn(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array($this->ugroup_03);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturn(true);
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForRepository')->with($this->repository)->andReturn(false);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertSame($config, $expected);
    }

    public function testItDoesntUpdatePatternWhenRegexpAreEnabled()
    {
        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturn(array(1 => $this->permission_01));
        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturn(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array($this->ugroup_03);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturn(true);
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForRepository')->with($this->repository)->andReturn(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master = @ug_103
 RW refs/heads/master = @ug_101 @ug_102
 - refs/heads/master = @all
 RW+ refs/tags/v1 = @ug_103
 RW refs/tags/v1 = @ug_101 @ug_102
 - refs/tags/v1 = @all

EOS;

        $this->assertSame($config, $expected);
    }
}
