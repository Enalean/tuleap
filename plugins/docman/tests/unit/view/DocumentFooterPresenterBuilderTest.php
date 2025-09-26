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

declare(strict_types=1);

namespace Tuleap\Docman\view;

use PFUser;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentFooterPresenterBuilderTest extends TestCase
{
    private PFUser $user;
    private int $project_id;
    private DocumentFooterPresenterBuilder $builder;

    #[\Override]
    public function setUp(): void
    {
        $this->user = UserTestBuilder::anActiveUser()->build();

        $project          = ProjectTestBuilder::aProject()->withUnixName('projectshortname')->build();
        $this->project_id = (int) $project->getID();

        $project_manager = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->with($this->project_id)->willReturn($project);

        $this->builder = new DocumentFooterPresenterBuilder($project_manager);
    }

    public function testItShouldNotAddLinkWhenFolderIsNotInAMigratedView(): void
    {
        $item   = [
            'parent_id' => 0,
            'item_id'   => 100,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
        ];
        $params = ['item' => $item];

        self::assertCount(0, $this->builder->build($params, $this->project_id, $item, $this->user)->links);
    }

    public function testItShouldAddLinkWhenFolderIsInAMigratedView(): void
    {
        $item   = [
            'parent_id' => 3,
            'item_id'   => 100,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
        ];
        $params = [
            'item'   => $item,
            'action' => 'show',
        ];

        self::assertCount(1, $this->builder->build($params, $this->project_id, $item, $this->user)->links);
    }
}
