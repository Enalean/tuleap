<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\common\Project\Admin\DescriptionFields;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\Project\Admin\DescriptionFields\FieldUpdator;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollection;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;

final class FieldUpdatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LoggerInterface $logger;
    private DefaultProjectVisibilityRetriever $default_project_visibility_retriever;

    private DescriptionFieldsFactory&MockObject $field_factory;
    private ProjectDetailsDAO&MockObject $dao;
    private FieldUpdator $updater;

    protected function setUp(): void
    {
        $this->default_project_visibility_retriever = new DefaultProjectVisibilityRetriever();
        $this->field_factory                        = $this->createMock(DescriptionFieldsFactory::class);
        $this->dao                                  = $this->createMock(ProjectDetailsDAO::class);
        $this->logger                               = new NullLogger();
        $this->updater                              = new FieldUpdator($this->field_factory, $this->dao, $this->logger);
    }

    public function testItUpdatesField(): void
    {
        $group_id     = 101;
        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );
        $project_data->setDataFields(
            ProjectRegistrationSubmittedFieldsCollection::buildFromArray([
                1 => 'My field 1 content',
                2 => 'Other content for field 2',
            ])
        );

        $this->field_factory->method('getAllDescriptionFields')->willReturn(
            [
                ['group_desc_id' => 1],
                ['group_desc_id' => 2],
                ['group_desc_id' => 3],
            ]
        );

        $this->dao
            ->expects(self::exactly(2))
            ->method('createGroupDescription')
            ->withConsecutive(
                [$group_id, 1, 'My field 1 content'],
                [$group_id, 2, 'Other content for field 2']
            )
            ->willReturnOnConsecutiveCalls(100, 101);

        $this->updater->update($project_data, $group_id);
    }

    public function testItLogsIfUpdateFail(): void
    {
        $group_id     = 101;
        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );
        $project_data->setDataFields(
            ProjectRegistrationSubmittedFieldsCollection::buildFromArray([
                1 => 'My field 1 content',
            ])
        );

        $this->field_factory->method('getAllDescriptionFields')->willReturn(
            [
                ['group_desc_id' => 1],
                ['group_desc_id' => 2],
                ['group_desc_id' => 3],
            ]
        );

        $this->dao
            ->expects(self::once())
            ->method('createGroupDescription')
            ->with($group_id, 1, 'My field 1 content')
            ->willReturn(false);

        $this->updater->update($project_data, $group_id);
    }
}
