<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class GenericUserFactoryTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;

    protected function setUp() : void
    {
        parent::setUp();
        $this->user_manager = \Mockery::spy(\UserManager::class);
        $this->project_manager = \Mockery::spy(\ProjectManager::class);
        $this->project = \Mockery::spy(\Project::class);
        $this->project_manager->shouldReceive('getProject')->andReturns($this->project);
        $dao = \Mockery::spy(\GenericUserDao::class);

        ForgeConfig::store();

        ForgeConfig::set(GenericUserFactory::CONFIG_KEY_SUFFIX, '');
        $this->factory = new GenericUserFactory($this->user_manager, $this->project_manager, $dao);
    }

    protected function tearDown() : void
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testCreateReturnsGenericUserWithCorrectId() : void
    {
        $group_id = '120';
        $password = 'my_password';

        $generic_user = $this->factory->create($group_id, $password);
        $this->assertInstanceOf(\GenericUser::class, $generic_user);

        $this->assertEquals($generic_user->getPassword(), 'my_password');
        $this->assertEquals($generic_user->getProject(), $this->project);
    }

    public function testItCreatesUserWithNoSuffixByDefault() : void
    {
        $project_name = 'vla';
        $this->project->shouldReceive('getUnixName')->andReturns($project_name);

        $generic_user = $this->factory->create('120', 'my_password');
        $this->assertEquals(substr($generic_user->getUnixName(), -strlen($project_name)), $project_name);
    }

    public function testItCreatesUserWithPrefixSetFromConfig() : void
    {
        $suffix = '-team';
        ForgeConfig::set(GenericUserFactory::CONFIG_KEY_SUFFIX, $suffix);

        $generic_user = $this->factory->create('120', 'my_password');
        $this->assertEquals(substr($generic_user->getUnixName(), -strlen($suffix)), $suffix);
    }
}
