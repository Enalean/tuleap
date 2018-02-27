<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use ProjectUGroup;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use TuleapTestCase;

class PHPWikiPermissionPerGroupPaneBuilderTest extends TuleapTestCase
{
    /**
     * @var PHPWikiPermissionPerGroupPaneBuilder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->wiki_permissions_manager = mock('Wiki_PermissionsManager');
        $this->ugroup_manager           = mock('UGroupManager');
        $this->formatter                = new PermissionPerGroupUGroupFormatter($this->ugroup_manager);

        $renderer         = mock('TemplateRenderer');
        $template_factory = stub('TemplateRendererFactory')->getRenderer()->returns($renderer);

        $this->builder = new PHPWikiPermissionPerGroupPaneBuilder(
            $this->wiki_permissions_manager,
            $this->formatter,
            mock('UGroupManager'),
            $template_factory
        );

        $this->project = aMockProject()->build();

        stub($this->wiki_permissions_manager)->getWikiAdminsGroups()->returns(
            array(ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::WIKI_ADMIN)
        );
    }

    public function itDoesNotBuildPaneIfServiceNotUsed()
    {
        stub($this->project)->usesWiki()->returns(false);

        $selected_ugroup_id = null;

        expect($this->ugroup_manager)->getUGroup()->never();
        expect($this->wiki_permissions_manager)->getWikiAdminsGroups()->never();
        expect($this->wiki_permissions_manager)->getWikiServicePermissions()->never();

        $this->builder->getPaneContent($this->project, $selected_ugroup_id);
    }

    public function itExportsServicePermissions()
    {
        stub($this->project)->usesWiki()->returns(true);
        stub($this->wiki_permissions_manager)->getWikiServicePermissions($this->project)->returns(
            array(ProjectUGroup::REGISTERED)
        );

        $selected_ugroup_id = null;

        $this->ugroup_manager->expectCallCount('getUGroup', 3);
        expect($this->wiki_permissions_manager)->getWikiAdminsGroups()->once();
        expect($this->wiki_permissions_manager)->getWikiServicePermissions($this->project)->once();

        $this->builder->getPaneContent($this->project, $selected_ugroup_id);
    }
}
