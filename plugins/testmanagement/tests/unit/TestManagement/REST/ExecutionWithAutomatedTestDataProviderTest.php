<?php
/**
 * Copyright (c) Enalean, 2020- Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST;

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestData;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestDataProvider;
use Tuleap\Tracker\Artifact\Artifact;

class ExecutionWithAutomatedTestDataProviderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExecutionDao
     */
    private $execution_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var ExecutionWithAutomatedTestDataProvider
     */
    private $execution_with_automated_test_data_provider;

    protected function setUp(): void
    {
        $this->execution_dao                               = Mockery::mock(ExecutionDao::class);
        $this->form_element_factory                        = Mockery::mock(Tracker_FormElementFactory::class);
        $this->execution_with_automated_test_data_provider = new ExecutionWithAutomatedTestDataProvider(
            $this->execution_dao,
            $this->form_element_factory
        );
    }

    public function testGetExecutionWithAutomatedTestData(): void
    {
        $changeset = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $execution = Mockery::mock(Artifact::class);
        $execution->shouldReceive('getId')->andReturn(12);
        $automated_test = Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $automated_test->shouldReceive('getText')->andReturn("automated test");

        $definition = Mockery::mock(Artifact::class);
        $definition->shouldReceive('getTrackerId')->andReturn(112);
        $definition->shouldReceive('getChangeset')->andReturn($changeset);
        $definition->shouldReceive('getValue')->andReturn($automated_test);

        $user  = Mockery::mock(PFUser::class);
        $field = Mockery::mock(Tracker_FormElement_Field::class);

        $this->execution_dao->shouldReceive('searchDefinitionChangesetIdForExecution')->andReturn(12);
        $this->form_element_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn($field);

        $expected_result = new ExecutionWithAutomatedTestData($execution, "automated test");

        $result = $this->execution_with_automated_test_data_provider->getExecutionWithAutomatedTestData(
            $execution,
            $definition,
            $user
        );

        $this->assertEquals($expected_result, $result);
    }

    public function testGetExecutionWithAutomatedTestDataReturnNullIfNoChangesetId(): void
    {
        $execution = Mockery::mock(Artifact::class);
        $execution->shouldReceive('getId')->andReturn(12);
        $automated_test = Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $automated_test->shouldReceive('getText')->andReturn("automated test");

        $definition = Mockery::mock(Artifact::class);
        $definition->shouldReceive('getTrackerId')->andReturn(112);
        $definition->shouldReceive('getChangeset')->never();
        $definition->shouldReceive('getValue')->andReturn($automated_test);

        $user  = Mockery::mock(PFUser::class);
        $field = Mockery::mock(Tracker_FormElement_Field::class);

        $this->execution_dao->shouldReceive('searchDefinitionChangesetIdForExecution')->andReturn(false);
        $this->form_element_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn($field);

        $result = $this->execution_with_automated_test_data_provider->getExecutionWithAutomatedTestData(
            $execution,
            $definition,
            $user
        );

        $this->assertEquals(null, $result);
    }

    public function testGetExecutionWithAutomatedTestDataReturnNullIfNoField(): void
    {
        $execution = Mockery::mock(Artifact::class);
        $execution->shouldReceive('getId')->andReturn(12);
        $automated_test = Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $automated_test->shouldReceive('getText')->andReturn("automated test");

        $definition = Mockery::mock(Artifact::class);
        $definition->shouldReceive('getTrackerId')->andReturn(112);
        $definition->shouldReceive('getChangeset')->never();
        $definition->shouldReceive('getValue')->andReturn($automated_test);

        $user  = Mockery::mock(PFUser::class);

        $this->execution_dao->shouldReceive('searchDefinitionChangesetIdForExecution')->andReturn(12);
        $this->form_element_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn(null);

        $result = $this->execution_with_automated_test_data_provider->getExecutionWithAutomatedTestData(
            $execution,
            $definition,
            $user
        );

        $this->assertEquals(null, $result);
    }

    public function testGetExecutionWithAutomatedTestDataReturnNullIfNoChangeset(): void
    {
        $execution = Mockery::mock(Artifact::class);
        $execution->shouldReceive('getId')->andReturn(12);
        $automated_test = Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $automated_test->shouldReceive('getText')->andReturn("automated test");

        $definition = Mockery::mock(Artifact::class);
        $definition->shouldReceive('getTrackerId')->andReturn(112);
        $definition->shouldReceive('getChangeset')->andReturn(null);
        $definition->shouldReceive('getValue')->andReturn($automated_test);

        $user  = Mockery::mock(PFUser::class);
        $field = Mockery::mock(Tracker_FormElement_Field::class);

        $this->execution_dao->shouldReceive('searchDefinitionChangesetIdForExecution')->andReturn(12);
        $this->form_element_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn($field);

        $result = $this->execution_with_automated_test_data_provider->getExecutionWithAutomatedTestData(
            $execution,
            $definition,
            $user
        );

        $this->assertEquals(null, $result);
    }
}
