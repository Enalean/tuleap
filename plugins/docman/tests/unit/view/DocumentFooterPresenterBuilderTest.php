<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\view;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;

class DocumentFooterPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var int
     */
    private $project_id;

    /**
     * @var DocumentFooterPresenterBuilder
     */
    private $builder;
    /**
     * @var Project
     */
    private $project;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $this->project    = ProjectTestBuilder::aProject()->withUnixName('projectshortname')->build();
        $this->project_id = 101;

        $project_manager = Mockery::mock(ProjectManager::class);
        $project_manager->shouldReceive('getProject')->with($this->project_id)->andReturn($this->project);

        $this->builder = new DocumentFooterPresenterBuilder($project_manager);
    }

    public function testItShouldNotAddLinkWhenFolderIsNotInAMigratedView()
    {
        $item   = [
            "parent_id" => 0,
            "item_id"   => 100,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
        ];
        $params = [
            'item' => $item,
        ];

        self::assertCount(
            0,
            $this->builder
                ->build($params, $this->project_id, $item, $this->user)
                ->links
        );
    }

    public function testItShouldAddLinkWhenFolderIsInAMigratedView()
    {
        $item   = [
            "parent_id" => 3,
            "item_id"   => 100,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
        ];
        $params = [
            'item'   => $item,
            'action' => 'show',
        ];

        self::assertCount(
            1,
            $this->builder
                ->build($params, $this->project_id, $item, $this->user)
                ->links
        );
    }
}
