<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Renderer;

use Codendi_Request;
use PHPUnit\Framework\MockObject\Stub;
use ProjectManager;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\StatusValuesCollection;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldsDataFromRequestRetrieverTest extends TestCase
{
    private Tracker|Stub $tracker;
    private Artifact $artifact;
    private Tracker_FormElementFactory|Stub $form_element_factory;
    private Stub|FirstPossibleValueInListRetriever $first_possible_value_retriever;
    private FieldsDataFromRequestRetriever $fields_data_from_request_retriever;
    private Stub|Tracker_FormElement_Field_List $field;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::anActiveUser()->withId(114)->build();

        $this->field = $this->createStub(Tracker_FormElement_Field_List::class);
        $this->field->method('getId')->willReturn(123);

        $this->tracker = $this->createStub(Tracker::class);
        $this->tracker->method('getId')->willReturn(666);

        $this->artifact = ArtifactTestBuilder::anArtifact(12)->inTracker($this->tracker)->build();

        $this->form_element_factory           = $this->createStub(Tracker_FormElementFactory::class);
        $this->first_possible_value_retriever = $this->createStub(FirstPossibleValueInListRetriever::class);

        $this->fields_data_from_request_retriever = new FieldsDataFromRequestRetriever(
            $this->form_element_factory,
            $this->first_possible_value_retriever
        );
    }

    public function testItGetTheFirstPossibleValueWhenPossibleValuesAreInRequestParameters(): void
    {
        $expected_result = [123 => 2];
        $request         = new Codendi_Request(
            [
                'artifact' => [
                    'field_id'        => '123',
                    'possible_values' => '[1, 2, 3]',
                ],
            ],
            $this->createStub(ProjectManager::class)
        );

        $collection = new StatusValuesCollection([1, 2, 3]);
        $this->form_element_factory->method('getFieldById')->with(123)->willReturn($this->field);

        $this->first_possible_value_retriever->method('getFirstPossibleValue')
            ->with($this->artifact, $this->field, $collection)
            ->willReturn(2);

        $this->assertEquals(
            $expected_result,
            $this->fields_data_from_request_retriever->getAugmentedDataFromRequest(
                $this->artifact,
                $request,
                $this->user
            )
        );
    }

    public function testItGetAugmentDataFromRequestWhenNoPossibleValuesInParameters(): void
    {
        $expected_result = [123 => 12];
        $request         = new Codendi_Request(
            [
                'artifact' => [
                    '123' => '12',
                ],
            ],
            $this->createStub(ProjectManager::class)
        );

        $this->tracker->method('augmentDataFromRequest')->with(
            ['123' => '12', 'request_method_called' => 'artifact-update']
        )->willReturn([123 => 12]);

        $this->assertEquals(
            $expected_result,
            $this->fields_data_from_request_retriever->getAugmentedDataFromRequest(
                $this->artifact,
                $request,
                $this->user
            )
        );
    }
}
