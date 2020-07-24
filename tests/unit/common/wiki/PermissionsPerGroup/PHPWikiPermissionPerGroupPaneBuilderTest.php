<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\PHPWiki\PermissionsPerGroup;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;

class PHPWikiPermissionPerGroupPaneBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PHPWikiPermissionPerGroupPaneBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wiki_permissions_manager = \Mockery::spy(\Wiki_PermissionsManager::class);
        $this->ugroup_manager           = \Mockery::spy(\UGroupManager::class);
        $this->formatter                = new PermissionPerGroupUGroupFormatter($this->ugroup_manager);

        $renderer         = \Mockery::spy(\TemplateRenderer::class);
        $template_factory = \Mockery::spy(\TemplateRendererFactory::class)->shouldReceive('getRenderer')->andReturns($renderer)->getMock();

        $this->builder = new PHPWikiPermissionPerGroupPaneBuilder(
            $this->wiki_permissions_manager,
            $this->formatter,
            \Mockery::spy(\UGroupManager::class),
            $template_factory
        );

        $this->project = \Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);
    }

    public function testItDoesNotBuildPaneIfServiceNotUsed(): void
    {
        $this->project->shouldReceive('usesWiki')->andReturns(false);

        $selected_ugroup_id = null;

        $this->ugroup_manager->shouldReceive('getUGroup')->never();
        $this->wiki_permissions_manager->shouldReceive('getWikiAdminsGroups')->never();
        $this->wiki_permissions_manager->shouldReceive('getWikiServicePermissions')->never();

        $this->builder->getPaneContent($this->project, $selected_ugroup_id);
    }

    public function testItExportsServicePermissions(): void
    {
        $this->project->shouldReceive('usesWiki')->andReturns(true);
        $this->wiki_permissions_manager->shouldReceive('getWikiServicePermissions')->once()->with($this->project)->andReturns([ProjectUGroup::REGISTERED]);

        $selected_ugroup_id = null;

        $this->ugroup_manager->shouldReceive('getUGroup')->times(3);
        $this->wiki_permissions_manager->shouldReceive('getWikiAdminsGroups')->once()->andReturns([ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::WIKI_ADMIN]);

        $this->builder->getPaneContent($this->project, $selected_ugroup_id);
    }
}
