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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestData;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestDataProvider;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ExecutionWithAutomatedTestDataProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ExecutionDao&MockObject $execution_dao;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private ExecutionWithAutomatedTestDataProvider $execution_with_automated_test_data_provider;

    protected function setUp(): void
    {
        $this->execution_dao                               = $this->createMock(ExecutionDao::class);
        $this->form_element_factory                        = $this->createMock(Tracker_FormElementFactory::class);
        $this->execution_with_automated_test_data_provider = new ExecutionWithAutomatedTestDataProvider(
            $this->execution_dao,
            $this->form_element_factory
        );
    }

    public function testGetExecutionWithAutomatedTestData(): void
    {
        $changeset      = $this->createMock(\Tracker_Artifact_Changeset::class);
        $execution      = ArtifactTestBuilder::anArtifact(12)->build();
        $automated_test = $this->createMock(\Tracker_Artifact_ChangesetValue_Text::class);
        $automated_test->method('getText')->willReturn('automated test');

        $definition = $this->createMock(Artifact::class);
        $definition->method('getTrackerId')->willReturn(112);
        $definition->method('getChangeset')->willReturn($changeset);
        $definition->method('getValue')->willReturn($automated_test);

        $user  = $this->createMock(PFUser::class);
        $field = $this->createMock(Tracker_FormElement_Field::class);

        $this->execution_dao->method('searchDefinitionChangesetIdForExecution')->willReturn(12);
        $this->form_element_factory->method('getUsedFieldByNameForUser')->willReturn($field);

        $expected_result = new ExecutionWithAutomatedTestData($execution, 'automated test');

        $result = $this->execution_with_automated_test_data_provider->getExecutionWithAutomatedTestData(
            $execution,
            $definition,
            $user
        );

        $this->assertEquals($expected_result, $result);
    }

    public function testGetExecutionWithAutomatedTestDataReturnNullIfNoChangesetId(): void
    {
        $execution      = ArtifactTestBuilder::anArtifact(12)->build();
        $automated_test = $this->createMock(\Tracker_Artifact_ChangesetValue_Text::class);
        $automated_test->method('getText')->willReturn('automated test');

        $definition = $this->createMock(Artifact::class);
        $definition->method('getTrackerId')->willReturn(112);
        $definition->expects(self::never())->method('getChangeset');
        $definition->method('getValue')->willReturn($automated_test);

        $user  = $this->createMock(PFUser::class);
        $field = $this->createMock(Tracker_FormElement_Field::class);

        $this->execution_dao->method('searchDefinitionChangesetIdForExecution')->willReturn(false);
        $this->form_element_factory->method('getUsedFieldByNameForUser')->willReturn($field);

        $result = $this->execution_with_automated_test_data_provider->getExecutionWithAutomatedTestData(
            $execution,
            $definition,
            $user
        );

        $this->assertEquals(null, $result);
    }

    public function testGetExecutionWithAutomatedTestDataReturnNullIfNoField(): void
    {
        $execution      = ArtifactTestBuilder::anArtifact(12)->build();
        $automated_test = $this->createMock(\Tracker_Artifact_ChangesetValue_Text::class);
        $automated_test->method('getText')->willReturn('automated test');

        $definition = $this->createMock(Artifact::class);
        $definition->method('getTrackerId')->willReturn(112);
        $definition->expects(self::never())->method('getChangeset');
        $definition->method('getValue')->willReturn($automated_test);

        $user = $this->createMock(PFUser::class);

        $this->execution_dao->method('searchDefinitionChangesetIdForExecution')->willReturn(12);
        $this->form_element_factory->method('getUsedFieldByNameForUser')->willReturn(null);

        $result = $this->execution_with_automated_test_data_provider->getExecutionWithAutomatedTestData(
            $execution,
            $definition,
            $user
        );

        $this->assertEquals(null, $result);
    }

    public function testGetExecutionWithAutomatedTestDataReturnNullIfNoChangeset(): void
    {
        $execution      = ArtifactTestBuilder::anArtifact(12)->build();
        $automated_test = $this->createMock(\Tracker_Artifact_ChangesetValue_Text::class);
        $automated_test->method('getText')->willReturn('automated test');

        $definition = $this->createMock(Artifact::class);
        $definition->method('getTrackerId')->willReturn(112);
        $definition->method('getChangeset')->willReturn(null);
        $definition->method('getValue')->willReturn($automated_test);

        $user  = $this->createMock(PFUser::class);
        $field = $this->createMock(Tracker_FormElement_Field::class);

        $this->execution_dao->method('searchDefinitionChangesetIdForExecution')->willReturn(12);
        $this->form_element_factory->method('getUsedFieldByNameForUser')->willReturn($field);

        $result = $this->execution_with_automated_test_data_provider->getExecutionWithAutomatedTestData(
            $execution,
            $definition,
            $user
        );

        $this->assertEquals(null, $result);
    }
}
