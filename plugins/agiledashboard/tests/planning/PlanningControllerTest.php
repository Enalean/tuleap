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

require_once dirname(__FILE__).'/../../include/constants.php';
require_once dirname(__FILE__).'/../../include/Planning/MilestoneFactory.class.php';
require_once dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php';
require_once dirname(__FILE__).'/../../include/Planning/PlanningController.class.php';
require_once dirname(__FILE__).'/../../include/Planning/ShortAccess.class.php';
require_once dirname(__FILE__).'/../../include/Planning/MilestoneLinkPresenter.class.php';
require_once dirname(__FILE__).'/../../include/Planning/ShortAccessMilestonePresenter.class.php';


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

    /**
     *
     * @var Codendi_Request 
     */
    private $request;

    /**
     *
     * @var Planning_Controller
     */
    private $controller;

    /**
     *
     * @var User
     */
    private $user;

    /**
     *
     * @var Systray_LinksCollection
     */
    private $links;

    public function setUp() {
        parent::setUp();

        $this->user = mock('User');
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

    }

    public function testSystrayWillNotAddLinksIfuserHasNoProjects() {
        stub($this->user)->getGroups()->returns(array());
        
        $this->controller->systray();
        
        $this->assertEqual(0, $this->links->count());
    }

    public function testSystrayWillNotAddLinksIfuserHasAProjectButItDoesNotuseTheAgileDashboardPlugin() {
        $project = mock('Project');
        
        stub($project)->usesService('plugin_agiledashboard')->returns(false);
        stub($this->user)->getGroups()->returns(array($project));

        $this->controller->systray();

        $this->assertEqual(0, $this->links->count());
    }

    public function testSystrayWillNotAddALinkIfuserHasNoMilestonesInTheAgileDashboardPlugin() {
        $project = mock('Project');

        stub($project)->usesService('plugin_agiledashboard')->returns(true);
        stub($this->user)->getGroups()->returns(array($project));

        stub($this->planning_factory)->getPlanningsShortAccess()->returns(array());

        $this->controller->systray();

        $this->assertEqual(1, $this->links->count());
    }
    
    public function testSystrayWillAddLinkIfUserHasAMilestoneInTheAgileDashboardPluginWithTitle() {
        $milestone_title = 'my_sprint';
        $project = mock('Project');
        $planning_short_access = mock('Planning_ShortAccess');
        $milestone_presenter = mock('Planning_ShortAccessMilestonePresenter');

        stub($project)->usesService('plugin_agiledashboard')->returns(true);
        stub($this->user)->getGroups()->returns(array($project));

        $short_access_array = array($planning_short_access);
        stub($planning_short_access)->getLastOpenMilestones()->returns(array($milestone_presenter));
        stub($this->planning_factory)->getPlanningsShortAccess()->returns($short_access_array);

        stub($milestone_presenter)->isLatest()->returns(true);
        stub($milestone_presenter)->getTitle()->returns($milestone_title);

        $this->controller->systray();

        $this->assertEqual(1, $this->links->count());
        $this->assertIsA($this->links->offsetGet(0), 'Systray_Link');
        $link = $this->links->offsetGet(0);
        $this->assertPattern('/'.$milestone_title.'/', $link->label);
    }

    public function testSystrayWillAddALinkForEachMilestoneInEachProjectAgileDashboard() {
        $milestone_title1 = 'my_sprint1';
        $milestone_title2 = 'my_sprint2';

        $project1 = mock('Project');
        $project2 = mock('Project');
        stub($project1)->usesService('plugin_agiledashboard')->returns(true);
        stub($project2)->usesService('plugin_agiledashboard')->returns(true);
        stub($project1)->getId()->returns(111);
        stub($project2)->getId()->returns(222);
        $projects = array(
            $project1,
            $project2,
        );
        
        stub($this->user)->getGroups()->returns($projects);

        $planning_short_access1 = mock('Planning_ShortAccess');
        $milestone_presenter1 = mock('Planning_ShortAccessMilestonePresenter');
        stub($planning_short_access1)->getLastOpenMilestones()->returns(array($milestone_presenter1));
        $short_access_array1 = array($planning_short_access1);
        stub($this->planning_factory)
            ->getPlanningsShortAccess(
                $this->user,
                111,
                $this->milestone_factory,
                $this->plugin_theme_path
            )
            ->returns($short_access_array1);

        $planning_short_access2 = mock('Planning_ShortAccess');
        $milestone_presenter2 = mock('Planning_ShortAccessMilestonePresenter');
        stub($planning_short_access2)->getLastOpenMilestones()->returns(array($milestone_presenter2));
        $short_access_array2 = array($planning_short_access2);
        stub($this->planning_factory)
            ->getPlanningsShortAccess(
                $this->user,
                222,
                $this->milestone_factory,
                $this->plugin_theme_path
            )
            ->returns($short_access_array2);

        stub($milestone_presenter1)->isLatest()->returns(true);
        stub($milestone_presenter1)->getTitle()->returns($milestone_title1);

        stub($milestone_presenter2)->isLatest()->returns(true);
        stub($milestone_presenter2)->getTitle()->returns($milestone_title2);

        $this->controller->systray();

        $this->assertEqual(2, $this->links->count());

        $this->assertIsA($this->links->offsetGet(0), 'Systray_Link');
        $link1 = $this->links->offsetGet(0);
        $this->assertPattern('/'.$milestone_title1.'/', $link1->label);

        $this->assertIsA($this->links->offsetGet(1), 'Systray_Link');
        $link2 = $this->links->offsetGet(1);
        $this->assertPattern('/'.$milestone_title2.'/', $link2->label);
    }

    public function testSystrayWillAddLinkForLatestMilestoneInTheAgileDashboard() {
        $milestone_title = 'my_sprint';
        $project = mock('Project');
        $planning_short_access = mock('Planning_ShortAccess');
        $milestone_presenter1 = mock('Planning_ShortAccessMilestonePresenter');
        $milestone_presenter2 = mock('Planning_ShortAccessMilestonePresenter');

        $milestone_presenters = array(
            $milestone_presenter1,
            $milestone_presenter2,
        );

        stub($project)->usesService('plugin_agiledashboard')->returns(true);
        stub($this->user)->getGroups()->returns(array($project));

        $short_access_array = array($planning_short_access);
        stub($planning_short_access)->getLastOpenMilestones()->returns($milestone_presenters);
        stub($this->planning_factory)->getPlanningsShortAccess()->returns($short_access_array);

        stub($milestone_presenter1)->isLatest()->returns(true);
        stub($milestone_presenter1)->getTitle()->returns($milestone_title);

        stub($milestone_presenter1)->isLatest()->returns(false);

        $this->controller->systray();

        $this->assertEqual(1, $this->links->count());
        $this->assertIsA($this->links->offsetGet(0), 'Systray_Link');
        $link = $this->links->offsetGet(0);
        $this->assertPattern('/'.$milestone_title.'/', $link->label);
    }
}

?>
