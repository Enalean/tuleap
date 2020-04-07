<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\Tour;

use ArrayObject;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project_NotFoundException;
use ProjectManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap_CustomToursFactory;
use URL;

final class CustomToursFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    /** @var Tuleap_CustomToursFactory */
    protected $factory;

    protected $fixtures_dir;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var URL */
    protected $url_processor;

    /** @var PFUser */
    protected $user;

    protected function setUp(): void
    {
        $this->project_manager = \Mockery::spy(ProjectManager::class);
        $this->url_processor   = \Mockery::spy(URL::class);
        $this->fixtures_dir    = __DIR__ . '/_fixtures';
        $this->factory         = \Mockery::mock(\Tuleap_CustomToursFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->factory->__construct($this->project_manager, $this->url_processor);

        $this->user            = \Mockery::spy(\PFUser::class);
        ForgeConfig::set('sys_custom_incdir', $this->fixtures_dir);
        $this->user->shouldReceive('getLocale')->andReturns('es_CU');
    }

    public function testItDoesNotGetToursIfCustomTourFolderDoesntExist(): void
    {
        $request_uri = '';
        ForgeConfig::set('sys_custom_incdir', $this->fixtures_dir . '/somewhereElse');
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getLocale')->andReturns('en_US');

        $tours = $this->factory->getToursForPage($user, $request_uri);
        $this->assertEmpty($tours);
    }

    public function testItGetsToursInCorrectLanguage(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getLocale')->andReturns('fr_FR');
        $request_uri = '/plugins/lala';

        $tours = $this->factory->getToursForPage($user, $request_uri);
        $this->assertEmpty($tours);
    }

    public function testItReturnsEmptyArrayIfNoAvailableTours(): void
    {
        $enabled_tours = array();
        $current_location = '/plugind/lala';
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertEmpty($valid_tours);
    }

    public function testItReturnsOnlyValidTours(): void
    {
        $this->project_manager->shouldReceive('getValidProject')->andReturns(\Mockery::spy(\Project::class));

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
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount(2, $valid_tours);

        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[0]);
        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[1]);
    }

    public function testItReturnsOnlyValidToursForCurrentLocation(): void
    {
        $this->url_processor->shouldReceive('getGroupIdFromUrl')->andReturn(101);
        $this->project_manager->shouldReceive('getValidProject')->with(101)->andReturns(\Mockery::spy(\Project::class));

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
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount(1, $valid_tours);

        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[0]);
    }

    public function testItManagesTheLocationWithoutPlaceholders(): void
    {
        $this->project_manager->shouldReceive('getValidProject')->andReturns(\Mockery::spy(\Project::class));

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
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount(1, $valid_tours);

        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[0]);
    }

    public function testItManagesTheLocationWithProjectId(): void
    {
        $this->url_processor->shouldReceive('getGroupIdFromUrl')->andReturns(144);
        $this->project_manager->shouldReceive('getValidProject')->andReturns(\Mockery::spy(\Project::class));

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/{project_id}/lala/'
            ),
        );
        $current_location = '/plugins/144/lala';
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount(1, $valid_tours);

        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[0]);
    }

    public function testItManagesTheLocationWithProjectName(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('jojo');
        $this->url_processor->shouldReceive('getGroupIdFromUrl')->andReturns(144);
        $this->project_manager->shouldReceive('getValidProject')->with(144)->andReturns($project);

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        $current_location = '/plugins/jojo/lala';
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount(1, $valid_tours);

        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[0]);
    }

    public function testItManagesThelocationWithProjectNameAndId(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('jojo');
        $this->url_processor->shouldReceive('getGroupIdFromUrl')->andReturns(144);
        $this->project_manager->shouldReceive('getValidProject')->with(144)->andReturns($project);

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/{project_name}/lala/{project_id}/?shortname=toto&true'
            ),
        );
        $current_location = '/plugins/jojo/lala/144/?shortname=toto&true';
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount(1, $valid_tours);

        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[0]);
    }

    public function testItManagesAttributes(): void
    {
        $this->project_manager->shouldReceive('getValidProject')->andThrows(new Project_NotFoundException());
        $placeholder = Tuleap_CustomToursFactory::PLACEHOLDER_ATTRIBUTE_VALUE;

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => "/plugins/lala/?drink=$placeholder&true"
            ),
        );
        $current_location = "/plugins/lala/?drink=tea&true";
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount(1, $valid_tours);

        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[0]);
    }

    public function testItManagesEverything(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('jojo');
        $this->url_processor->shouldReceive('getGroupIdFromUrl')->andReturns(144);
        $this->project_manager->shouldReceive('getValidProject')->with(144)->andReturns($project);

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
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertCount(1, $valid_tours);

        $this->assertInstanceOf(\Tuleap_Tour::class, $valid_tours[0]);
    }

    public function testItFailsIfAttributeMissing(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('jojo');
        $this->url_processor->shouldReceive('getGroupIdFromUrl')->andReturns(144);
        $this->project_manager->shouldReceive('getValidProject')->with(144)->andReturns($project);

        $id_placeholder   = Tuleap_CustomToursFactory::PLACEHOLDER_PROJECT_ID;
        $name_placeholder = Tuleap_CustomToursFactory::PLACEHOLDER_PROJECT_NAME;

        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => "/plugins/lala/$name_placeholder/?true&group_id=$id_placeholder"
            ),
        );
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));
        $current_location = "/plugins/lala/jojo/?drink=coffee&true&group_id=144";

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertEmpty($valid_tours);
    }

    public function testItIgnoresInvalidToursInEnabledList(): void
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
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($bad_enabled_tours));
        $current_location = '/plugind/lala';

        $valid_tours = $this->factory->getToursForPage($this->user, $current_location);

        $this->assertEmpty($valid_tours);
    }

    public function testItThrowsAnExceptionIfFileNotFound(): void
    {
        $enabled_tours = array(
            array(
                'tour_name' => 'woofwoof_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $this->expectException(\Tuleap_UnknownTourException::class);
        $this->factory->getTour($this->user, 'woofwoof_tour');
    }

    public function testItThrowsAnExceptionIfInvalidJsonArray(): void
    {
        $enabled_tours = array(
            array(
                'tour_name' => 'my_second_invalid_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $this->expectException(\Tuleap_InvalidTourException::class);
        $this->factory->getTour($this->user, 'my_second_invalid_tour');
    }

    public function testItThrowsAnExceptionIfTourDoesNotHaveSteps(): void
    {
        $enabled_tours = array(
            array(
                'tour_name' => 'my_invalid_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $this->expectException(\Tuleap_InvalidTourException::class);
        $this->factory->getTour($this->user, 'my_invalid_tour');
    }

    public function testItValidatesAGoodTour(): void
    {
        $enabled_tours = array(
            array(
                'tour_name' => 'my_valid_tour',
                'url'       => '/plugins/{project_name}/lala/'
            ),
        );
        $this->factory->shouldReceive('getTourListJson')->andReturns(json_encode($enabled_tours));

        $tour = $this->factory->getTour($this->user, 'my_valid_tour');

        $this->assertInstanceOf(\Tuleap_Tour::class, $tour);
    }
}
