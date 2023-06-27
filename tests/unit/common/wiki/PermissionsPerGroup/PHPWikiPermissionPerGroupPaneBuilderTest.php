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

use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Service;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class PHPWikiPermissionPerGroupPaneBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PHPWikiPermissionPerGroupPaneBuilder $builder;
    private MockObject&\Wiki_PermissionsManager $wiki_permissions_manager;
    private MockObject&\UGroupManager $ugroup_manager;
    private PermissionPerGroupUGroupFormatter $formatter;
    private MockObject&\TemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wiki_permissions_manager = $this->createMock(\Wiki_PermissionsManager::class);
        $this->ugroup_manager           = $this->createMock(\UGroupManager::class);
        $this->formatter                = new PermissionPerGroupUGroupFormatter($this->ugroup_manager);

        $this->renderer   = $this->createMock(\TemplateRenderer::class);
        $template_factory = $this->createMock(\TemplateRendererFactory::class);
        $template_factory->method('getRenderer')->willReturn($this->renderer);

        $this->builder = new PHPWikiPermissionPerGroupPaneBuilder(
            $this->wiki_permissions_manager,
            $this->formatter,
            $this->ugroup_manager,
            $template_factory
        );
    }

    public function testItDoesNotBuildPaneIfServiceNotUsed(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->withAccessPublic()->withoutServices()->build();

        $selected_ugroup_id = null;

        $this->ugroup_manager->expects(self::never())->method('getUGroup');
        $this->wiki_permissions_manager->expects(self::never())->method('getWikiAdminsGroups');
        $this->wiki_permissions_manager->expects(self::never())->method('getWikiServicePermissions');

        $this->builder->getPaneContent($project, $selected_ugroup_id);
    }

    public function testItExportsServicePermissions(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->withAccessPublic()->withUsedService(Service::WIKI)->build();

        $this->wiki_permissions_manager->expects(self::once())->method('getWikiServicePermissions')->with($project)->willReturn([ProjectUGroup::REGISTERED]);

        $selected_ugroup_id = null;
        $this->ugroup_manager->method('getUGroup')->willReturn(null);
        $this->wiki_permissions_manager->expects(self::once())->method('getWikiAdminsGroups')->willReturn([ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::WIKI_ADMIN]);

        $this->renderer->method('renderToString');

        $this->builder->getPaneContent($project, $selected_ugroup_id);
    }
}
