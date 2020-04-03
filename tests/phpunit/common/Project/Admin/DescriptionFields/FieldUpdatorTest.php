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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectCreationData;
use Psr\Log\LoggerInterface;
use Tuleap\Project\Admin\DescriptionFields\FieldDoesNotExistException;
use Tuleap\Project\Admin\DescriptionFields\FieldUpdator;
use Tuleap\Project\Admin\DescriptionFields\MissingMandatoryFieldException;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;

final class FieldUpdatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var DefaultProjectVisibilityRetriever
     */
    private $default_project_visibility_retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DescriptionFieldsFactory
     */
    private $field_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectDetailsDAO
     */
    private $dao;
    /**
     * @var FieldUpdator
     */
    private $updater;

    protected function setUp(): void
    {
        $this->default_project_visibility_retriever = new DefaultProjectVisibilityRetriever();
        $this->field_factory                        = \Mockery::mock(DescriptionFieldsFactory::class);
        $this->dao                                  = \Mockery::mock(ProjectDetailsDAO::class);
        $this->logger                               = \Mockery::mock(LoggerInterface::class);
        $this->updater                              = new FieldUpdator($this->field_factory, $this->dao, $this->logger);
    }

    public function testItUpdatesField(): void
    {
        $group_id = 101;
        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'form_1' => 'My field 1 content',
                    'form_2' => 'Other content for field 2'
                ],
            ]
        );

        $this->field_factory->shouldReceive('getAllDescriptionFields')->andReturn(
            [
                ['group_desc_id' => 1],
                ['group_desc_id' => 2],
                ['group_desc_id' => 3],
            ]
        );

        $this->dao->shouldReceive('createGroupDescription')->withArgs([$group_id, 1, 'My field 1 content'])->once()->andReturn(100);
        $this->dao->shouldReceive('createGroupDescription')->withArgs([$group_id, 2, 'Other content for field 2'])->once()->andReturn(101);

        $this->logger->shouldReceive('debug')->never();

        $this->updater->update($project_data, $group_id);
    }

    public function testItLogsIfUpdateFail(): void
    {
        $group_id = 101;
        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'form_1' => 'My field 1 content'
                ],
            ]
        );

        $this->field_factory->shouldReceive('getAllDescriptionFields')->andReturn(
            [
                ['group_desc_id' => 1],
                ['group_desc_id' => 2],
                ['group_desc_id' => 3],
            ]
        );

        $this->dao->shouldReceive('createGroupDescription')->withArgs([$group_id, 1, 'My field 1 content'])->once()->andReturn(false);

        $this->logger->shouldReceive('debug')->once();

        $this->updater->update($project_data, $group_id);
    }

    public function testItUpdatesFieldFromArray(): void
    {
        $group_id = 101;
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn($group_id);
        $fields = [
            1 => 'My field 1 content',
            2 => 'Other content for field 2'
        ];

        $this->field_factory->shouldReceive('getAllDescriptionFields')->andReturn(
            [
                ['group_desc_id' => 1],
                ['group_desc_id' => 2],
                ['group_desc_id' => 3],
            ]
        );

        $this->dao->shouldReceive('createGroupDescription')->withArgs([$group_id, 1, 'My field 1 content'])->once()->andReturn(100);
        $this->dao->shouldReceive('createGroupDescription')->withArgs([$group_id, 2, 'Other content for field 2'])->once()->andReturn(101);

        $this->logger->shouldReceive('debug')->never();

        $this->updater->updateFromArray($fields, $project);
    }

    public function testItLogsIfUpdateFromArrayFail(): void
    {
        $group_id = 101;
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn($group_id);
        $fields = [1 => 'My field 1 content'];

        $this->field_factory->shouldReceive('getAllDescriptionFields')->andReturn(
            [
                ['group_desc_id' => 1],
                ['group_desc_id' => 2],
                ['group_desc_id' => 3],
            ]
        );

        $this->dao->shouldReceive('createGroupDescription')->withArgs([$group_id, 1, 'My field 1 content'])->once()->andReturn(false);

        $this->logger->shouldReceive('debug')->once();

        $this->updater->updateFromArray($fields, $project);
    }

    public function testExceptionIsThrownWhenSomeFieldsAreMissing(): void
    {
        $this->field_factory->shouldReceive('getAllDescriptionFields')->andReturn(
            [
                ['group_desc_id' => 1, 'desc_required' => true, 'desc_name' => "field_name"]
            ]
        );

        $field_collection = [];

        $this->expectException(MissingMandatoryFieldException::class);
        $this->updater->checkFieldConsistency($field_collection);
    }

    public function testExceptionIsThrownWhenUserProvidesFieldsWhoDoesNotExists(): void
    {
        $this->field_factory->shouldReceive('getAllDescriptionFields')->andReturn(
            [
                ['group_desc_id' => 1, 'desc_required' => false, 'desc_name' => "field_name"]
            ]
        );

        $field_collection[2] = 'test';

        $this->expectException(FieldDoesNotExistException::class);
        $this->updater->checkFieldConsistency($field_collection);
    }

    public function testFieldConsistencyIsValidWhenEverythingIsOk(): void
    {
        $this->field_factory->shouldReceive('getAllDescriptionFields')->andReturn(
            [
                ['group_desc_id' => 1, 'desc_required' => true, 'desc_name' => "field_name"],
                ['group_desc_id' => 2, 'desc_required' => false, 'desc_name' => "other_field_name"]
            ]
        );

        $field_collection[1] = 'test';

        $this->updater->checkFieldConsistency($field_collection);
    }
}
