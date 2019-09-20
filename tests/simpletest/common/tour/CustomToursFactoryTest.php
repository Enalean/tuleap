<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class CustomToursFactoryTest extends TuleapTestCase
{

    /** @var Tuleap_CustomToursFactory */
    protected $factory;

    protected $fixtures_dir;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var URL */
    protected $url_processor;

    /** @var PFUser */
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->project_manager = mock('ProjectManager');
        $this->url_processor   = mock('Url');
        $this->fixtures_dir    = dirname(__FILE__) .'/_fixtures';
        $this->factory         = partial_mock('Tuleap_CustomToursFactory', array('getTourListJson'), array($this->project_manager, $this->url_processor));

        $this->user            = mock('PFUser');
        ForgeConfig::set('sys_custom_incdir', $this->fixtures_dir);
        stub($this->user)->getLocale()->returns('es_CU');
    }
}

class CustomTourFactoryTest_getToursForPage extends CustomToursFactoryTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function itDoesNotGetToursIfCustomTourFolderDoesntExist()
    {
        $request_uri = '';
        ForgeConfig::set('sys_custom_incdir', $this->fixtures_dir.'/somewhereElse');
        $user = mock('PFUser');
        stub($user)->getLocale()->returns('en_US');

        $tours = $this->factory->getToursForPage($user, $request_uri);
        $this->assertEqual(count($tours), 0);
    }

    public function itgetsToursInCorrectLanguage()
    {
        $user = mock('PFUser');
        stub($user)->getLocale()->returns('fr_FR');
        $request_uri = '/plugins/lala';

        $tours = $this->factory->getToursForPage($user, $request_uri);
        $this->assertEqual(count($tours), 0);
    }

    public function itReturnsEmptyArrayIfNoAvailableTours()
    {
        $enabled_tours = array();
        $current_location = '/plugind/lala';
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertArrayEmpty($valid_tours);
    }

    public function itReturnsOnlyValidTours()
    {
        stub($this->project_manager)->getValidProject()->returns(mock('Project'));

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/lala'
            ),
            array(
                'tour_name' => 'my_second_valid_tour',
                'url'       => '/plugins/lala'
            ),
            array(
                'tour_name' => 'my_invalid_tour',
                'url'       => '/plugins/lala'
            ),
            array(
                'tour_name' => 'my_second_invalid_tour',
                'url'       => '/plugins/lala'
            ),
            array(
                'tour_name' => 'my_doesNotExist_tour',
                'url'       => '/plugins/lala'
            ),
        );
        $current_location = '/plugins/lala';
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount($valid_tours, 2);

        $this->assertIsA($valid_tours[0], 'Tuleap_Tour');
        $this->assertIsA($valid_tours[1], 'Tuleap_Tour');
    }

    public function itReturnsOnlyValidToursForCurrentLocation()
    {
        stub($this->project_manager)->getValidProject()->returns(mock('Project'));

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/lala'
            ),
            array(
                'tour_name' => 'my_second_valid_tour',
                'url'       => '/plugins/nono'
            ),
        );
        $current_location = '/plugins/lala';
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount($valid_tours, 1);

        $this->assertIsA($valid_tours[0], 'Tuleap_Tour');
    }

    public function itManagesThelocationWithoutPlaceholders()
    {
        stub($this->project_manager)->getValidProject()->returns(mock('Project'));

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/lala'
            ),
            array(
                'tour_name' => 'my_second_valid_tour',
                'url'       => '/plugins/lala?shortname=toto&true'
            ),
        );
        $current_location = '/plugins/lala?shortname=toto&true';
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount($valid_tours, 1);

        $this->assertIsA($valid_tours[0], 'Tuleap_Tour');
    }

    public function itManagesThelocationWithProjectId()
    {
        stub($this->url_processor)->getGroupIdFromUrl()->returns(144);
        stub($this->project_manager)->getValidProject()->returns(mock('Project'));

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/{project_id}/lala/'
            ),
        );
        $current_location = '/plugins/144/lala';
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount($valid_tours, 1);

        $this->assertIsA($valid_tours[0], 'Tuleap_Tour');
    }

    public function itManagesThelocationWithProjectName()
    {
        $project = mock('Project');
        stub($project)->getUnixName()->returns('jojo');
        stub($this->url_processor)->getGroupIdFromUrl()->returns(144);
        stub($this->project_manager)->getValidProject(144)->returns($project);

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        $current_location = '/plugins/jojo/lala';
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount($valid_tours, 1);

        $this->assertIsA($valid_tours[0], 'Tuleap_Tour');
    }

    public function itManagesThelocationWithProjectNameAndId()
    {
        $project = mock('Project');
        stub($project)->getUnixName()->returns('jojo');
        stub($this->url_processor)->getGroupIdFromUrl()->returns(144);
        stub($this->project_manager)->getValidProject(144)->returns($project);

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/{project_name}/lala/{project_id}/?shortname=toto&true'
            ),
        );
        $current_location = '/plugins/jojo/lala/144/?shortname=toto&true';
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount($valid_tours, 1);

        $this->assertIsA($valid_tours[0], 'Tuleap_Tour');
    }

    public function itManagesAttributes()
    {
        stub($this->project_manager)->getValidProject()->throws(new Project_NotFoundException());
        $placeholder = Tuleap_CustomToursFactory::PLACEHOLDER_ATTRIBUTE_VALUE;

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => "/plugins/lala/?drink=$placeholder&true"
            ),
        );
        $current_location = "/plugins/lala/?drink=tea&true";
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount($valid_tours, 1);

        $this->assertIsA($valid_tours[0], 'Tuleap_Tour');
    }

    public function itManagesEverything()
    {
        $project = mock('Project');
        stub($project)->getUnixName()->returns('jojo');
        stub($this->url_processor)->getGroupIdFromUrl()->returns(144);
        stub($this->project_manager)->getValidProject(144)->returns($project);

        $attr_placeholder = Tuleap_CustomToursFactory::PLACEHOLDER_ATTRIBUTE_VALUE;
        $id_placeholder   = Tuleap_CustomToursFactory::PLACEHOLDER_PROJECT_ID;
        $name_placeholder = Tuleap_CustomToursFactory::PLACEHOLDER_PROJECT_NAME;

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => "/plugins/lala/$name_placeholder/?drink=$attr_placeholder&true&group_id=$id_placeholder&food=$attr_placeholder"
            ),
        );
        $current_location = "/plugins/lala/jojo/?drink=coffee&true&group_id=144&food=sandwich";
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount($valid_tours, 1);

        $this->assertIsA($valid_tours[0], 'Tuleap_Tour');
    }

    public function itFailsIfAttributeMissing()
    {
        $project = mock('Project');
        stub($project)->getUnixName()->returns('jojo');
        stub($this->url_processor)->getGroupIdFromUrl()->returns(144);
        stub($this->project_manager)->getValidProject(144)->returns($project);

        $id_placeholder   = Tuleap_CustomToursFactory::PLACEHOLDER_PROJECT_ID;
        $name_placeholder = Tuleap_CustomToursFactory::PLACEHOLDER_PROJECT_NAME;

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => "/plugins/lala/$name_placeholder/?true&group_id=$id_placeholder"
            ),
        );
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));
        $current_location = "/plugins/lala/jojo/?drink=coffee&true&group_id=144";

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertArrayEmpty($valid_tours);
    }

    public function itIgnoresInvalidToursInEnabledList()
    {
        $bad_enabled_tours = array(
            array (
                'url' => array(789),
                'tour_name' => new ArrayObject(array())
            ),
            array(
                'tata' => 'titi',
                'zaza' => array(
                    4566
                )
            ),
            'not an array',
            6666666,
        );
        stub($this->factory)->getTourListJson()->returns(json_encode($bad_enabled_tours));
        $current_location = '/plugind/lala';

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertArrayEmpty($valid_tours);
    }
}

class CustomTourFactoryTest_getTour extends CustomToursFactoryTest
{

    public function itThrowsAnExceptionIfFileNotFound()
    {
        $this->expectException('Tuleap_UnknownTourException');

        $enabled_tours = array(
            array(
                'tour_name' => 'woofwoof_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $this->factory->getTour($this->user, 'woofwoof_tour');
    }

    public function itThrowsAnExceptionIfInvalidJsonArray()
    {
        $this->expectException('Tuleap_InvalidTourException');

        $enabled_tours = array(
            array(
                'tour_name' => 'my_second_invalid_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $this->factory->getTour($this->user, 'my_second_invalid_tour');
    }

    public function itThrowsAnExceptionIfTourDoesNotHaveSteps()
    {
        $this->expectException('Tuleap_InvalidTourException');

        $enabled_tours = array(
            array(
                'tour_name' => 'my_invalid_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $this->factory->getTour($this->user, 'my_invalid_tour');
    }

    public function itValidatesAGoodTour()
    {
        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        stub($this->factory)->getTourListJson()->returns(json_encode($enabled_tours));

        $tour = $this->factory->getTour($this->user, 'my_valid_tour');

        $this->assertIsA($tour, 'Tuleap_Tour');
    }
}
