<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/Systray/LinksCollection.class.php';
require_once 'common/project/Project.class.php';

abstract class PlanningControllerTest extends TuleapTestCase {

    /**
     *
     * @var int
     */
    protected $group_id;

    /**
     *
     * @var PlanningFactory
     */
    protected $planning_factory;

    /**
     *
     * @var Planning_MilestoneFactory
     */
    protected $milestone_factory;

    /**
     *
     * @var string
     */
    protected $plugin_theme_path;


    public function setUp() {
        Config::store();
        Config::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');

        parent::setUp();

        $plugin_theme_path = 'plugin/theme/path';

        $this->group_id          = 999;
        $this->planning_factory  = mock('PlanningFactory');
        $this->milestone_factory = mock('Planning_MilestoneFactory');
        $this->plugin_theme_path = $plugin_theme_path;

    }

     public function tearDown() {
        Config::restore();
        parent::tearDown();
    }

}

class PlanningControllerTest_systrayTest extends PlanningControllerTest {

    /** @var Codendi_Request  */
    private $request;

    /** @var Planning_Controller */
    private $controller;

    /** @var User */
    private $user;

    /** @var Systray_LinksCollection */
    private $links;

    /** @var Project */
    private $project;

    /** @var string */
    private $garden_project_name = 'Garden Project';
    private $gpig_project_name   = 'Guinea Pig Project';
    private $sprint_34_title     = 'Sprint 34';
    private $release_2_title     = 'Sprint 34';

    public function setUp() {
        parent::setUp();

        $this->user = mock('PFUser');
        $this->links = new Systray_LinksCollection();

        $params = array(
           'user' => $this->user,
           'links'=> $this->links,
        );

        $this->request = new Codendi_Request($params);

        $controller_mocked_methods = array('getCurrentUser');
        $controller_contructor_params = array(
            $this->request,
            $this->planning_factory,
            $this->milestone_factory,
            $this->plugin_theme_path
        );

        $this->controller = partial_mock(
            'Planning_Controller',
            $controller_mocked_methods,
            $controller_contructor_params
        );
        stub($this->controller)->getCurrentUser()->returns($this->user);

        $this->garden_project = mock('Project');
        $this->gpig_project   = mock('Project');
        stub($this->garden_project)->getPublicName()->returns($this->garden_project_name);
        stub($this->gpig_project)->getPublicName()->returns($this->gpig_project_name);
        stub($this->garden_project)->getId()->returns(111);
        stub($this->gpig_project)->getId()->returns(222);
    }

    public function testSystrayWillNotAddLinksIfuserHasNoProjects() {
        stub($this->user)->getGroups()->returns(array());

        $this->controller->generateSystrayData();

        $this->assertEqual(0, count($this->links));
    }

    public function testSystrayWillNotAddLinksIfuserHasAProjectButItDoesNotuseTheAgileDashboardPlugin() {
        stub($this->garden_project)->usesService('plugin_agiledashboard')->returns(false);
        stub($this->user)->getGroups()->returns(array($this->garden_project));

        $this->controller->generateSystrayData();

        $this->assertEqual(0, count($this->links));
    }

    public function testSystrayWillAddALinkIfuserHasNoMilestonesInTheAgileDashboardPlugin() {
        stub($this->garden_project)->usesService('plugin_agiledashboard')->returns(true);
        stub($this->user)->getGroups()->returns(array($this->garden_project));
        stub($this->planning_factory)->getPlanningsShortAccess()->returns(array());

        $this->controller->generateSystrayData();

        $this->assertEqual(1, count($this->links));
        $this->assertIsA($this->links[0], 'Systray_Link');
        $this->assertEqual($this->garden_project_name, $this->links[0]->label);
    }

    public function testSystrayWillAddLinkIfUserHasAMilestoneInTheAgileDashboardPluginWithTitle() {
        stub($this->garden_project)->usesService('plugin_agiledashboard')->returns(true);
        stub($this->user)->getGroups()->returns(array($this->garden_project));
        $this->setProjectLatestMilestone($this->garden_project, $this->sprint_34_title);

        $this->controller->generateSystrayData();

        $this->assertPattern('/'.$this->sprint_34_title.'/', $this->links[0]->label);
    }

    public function testSystrayWillAddALinkForEachMilestoneInEachProjectAgileDashboard() {
        stub($this->garden_project)->usesService('plugin_agiledashboard')->returns(true);
        stub($this->gpig_project)->usesService('plugin_agiledashboard')->returns(true);
        stub($this->user)->getGroups()->returns(array($this->garden_project, $this->gpig_project));
        $this->setProjectLatestMilestone($this->garden_project, $this->sprint_34_title);
        $this->setProjectLatestMilestone($this->gpig_project, $this->release_2_title);

        $this->controller->generateSystrayData();

        $this->assertEqual(2, count($this->links));
        $this->assertPattern('/'.$this->sprint_34_title.'/', $this->links[0]->label);
        $this->assertPattern('/'.$this->release_2_title.'/', $this->links[1]->label);
    }

    public function testSystrayWillAddLinkForLatestMilestoneInTheAgileDashboard() {
        stub($this->garden_project)->usesService('plugin_agiledashboard')->returns(true);
        stub($this->user)->getGroups()->returns(array($this->garden_project));

        $planning_short_access = mock('Planning_ShortAccess');
        $milestone_presenters  = array(
            mock('Planning_ShortAccessMilestonePresenter'),
            mock('Planning_ShortAccessMilestonePresenter'),
        );

        stub($milestone_presenters[0])->isLatest()->returns(true);
        stub($milestone_presenters[0])->getTitle()->returns($this->sprint_34_title);
        stub($milestone_presenters[1])->isLatest()->returns(false);

        $short_access_array = array($planning_short_access);
        stub($planning_short_access)->getLastOpenMilestones()->returns($milestone_presenters);
        stub($this->planning_factory)->getPlanningsShortAccess()->returns($short_access_array);

        $this->controller->generateSystrayData();

        $this->assertEqual(1, count($this->links));
        $this->assertPattern('/'.$this->sprint_34_title.'/', $this->links[0]->label);
    }

    private function setProjectLatestMilestone(Project $project, $milestone_title) {
        $planning_short_access = mock('Planning_ShortAccess');
        $milestone_presenter   = mock('Planning_ShortAccessMilestonePresenter');

        stub($milestone_presenter)->isLatest()->returns(true);
        stub($milestone_presenter)->getTitle()->returns($milestone_title);

        $short_access_array = array($planning_short_access);
        stub($planning_short_access)->getLastOpenMilestones()->returns(array($milestone_presenter));
        stub($this->planning_factory)
            ->getPlanningsShortAccess(
                $this->user,
                $project->getId(),
                $this->milestone_factory,
                $this->plugin_theme_path
            )->returns($short_access_array);
    }
}

?>
