<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../common.php';

class Planning_MilestoneController4Tests extends Planning_MilestoneController {
    public $output = null;

    public function render($template_name, $presenter) {
        $this->output = $this->renderer->renderToString($template_name, $presenter);
    }
}

class MilestoneController_BreadcrumbsTest extends TuleapTestCase {
    private $plugin_path;
    private $product;
    private $release;
    private $sprint;

    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');

        $this->plugin_path = '/plugin/path';

        $this->product     = aMilestone()->withArtifact(aMockArtifact()->withId(1)->withTitle('Product X')->build())->build();
        $this->release     = aMilestone()->withArtifact(aMockArtifact()->withId(2)->withTitle('Release 1.0')->build())->build();
        $this->sprint      = aMilestone()->withArtifact(aMockArtifact()->withId(3)->withTitle('Sprint 1')->build())->build();
        $this->nomilestone = stub('Planning_NoMilestone')->getPlanning()->returns(mock('Planning'));

        $this->milestone_factory = mock('Planning_MilestoneFactory');
        $this->project_manager   = mock('ProjectManager');

        $this->current_user   = aUser()->build();
        $this->request        = aRequest()->withUser($this->current_user)->with('group_id', 102)->build();

        $this->project = mock('Project');
        stub($this->project_manager)->getProject(102)->returns($this->project);
    }

    public function tearDown() {
        Config::restore();
        parent::tearDown();
    }

    public function itHasNoBreadCrumbWhenThereIsNoMilestone() {
        stub($this->milestone_factory)->getBareMilestone()->returns($this->nomilestone);

        $breadcrumbs = $this->getBreadcrumbs();
        $this->assertIsA($breadcrumbs, 'BreadCrumb_NoCrumb');
    }

    public function itIncludesBreadcrumbsForParentMilestones() {
        $this->sprint->setAncestors(array($this->release, $this->product));
        stub($this->milestone_factory)->getBareMilestone()->returns($this->sprint);

        $breadcrumbs = $this->getBreadcrumbs();
        $this->assertEqualToBreadCrumbWithAllMilestones($breadcrumbs);
    }

    private function getBreadcrumbs() {
        $controller = partial_mock(
            'Planning_MilestoneController',
            array('buildContentView'),
            array(
                $this->request,
                $this->milestone_factory,
                $this->project_manager,
                mock('Planning_MilestonePaneFactory'),
                mock('AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory'),
                ''
            )
        );
        return $controller->getBreadcrumbs($this->plugin_path);
    }

    public function assertEqualToBreadCrumbWithAllMilestones($breadcrumbs) {
        $expected_crumbs = new BreadCrumb_Merger(
            new BreadCrumb_VirtualTopMilestone($this->plugin_path, $this->project),
            new BreadCrumb_Milestone($this->plugin_path, $this->product),
            new BreadCrumb_Milestone($this->plugin_path, $this->release),
            new BreadCrumb_Milestone($this->plugin_path, $this->sprint)
        );
        $this->assertEqual($expected_crumbs, $breadcrumbs);
    }
}


?>
