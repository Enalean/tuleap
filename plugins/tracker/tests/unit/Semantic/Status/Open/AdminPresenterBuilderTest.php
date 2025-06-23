<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Semantic\Status\Open;

use CSRFSynchronizerToken;
use Tracker_FormElementFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AdminPresenterBuilderTest extends TestCase
{
    private Tracker_FormElementFactory|\PHPUnit\Framework\MockObject\MockObject $form_element_factory;
    private \PHPUnit\Framework\MockObject\MockObject|SemanticDoneDao $dao;
    private CSRFSynchronizerToken $csrf_token;
    private AdminPresenterBuilder $presenter_builder;
    private \Tuleap\Tracker\Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker              = TrackerTestBuilder::aTracker()->withId(20)->build();
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->dao                  = $this->createMock(SemanticDoneDao::class);

        $this->csrf_token = $this->createMock(CSRFSynchronizerToken::class);

        $this->presenter_builder = new AdminPresenterBuilder($this->form_element_factory, $this->dao);
    }

    public function testItBuildsAPresenterWhenNoFieldsArePossible(): void
    {
        $semantic_status = new \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus($this->tracker, null);
        $this->form_element_factory->method('getUsedListFields')->willReturn([]);
        $this->dao->method('getSelectedValues')->willReturn([]);
        $presenter = $this->presenter_builder->build($semantic_status, $this->tracker, $this->csrf_token);

        self::assertEquals(
            new AdminPresenter(
                0,
                'Status',
                '/plugins/tracker/?tracker=20&func=admin-semantic&semantic=status',
                $this->csrf_token,
                false,
                [],
                false,
                [],
                TRACKER_BASE_URL . '/?tracker=20&func=admin-semantic',
                0,
                null
            ),
            $presenter
        );
    }

    public function testItBuildsAPresenterWhenNoStatusFieldIsDefined(): void
    {
        $field_A = $this->getFieldWithIdAndLabel(1, 'field A');
        $field_B = $this->getFieldWithIdAndLabel(2, 'field B');
        $this->form_element_factory->method('getUsedListFields')->willReturn([$field_A, $field_B]);
        $semantic_status = new \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus($this->tracker, null);
        $this->dao->method('getSelectedValues')->willReturn([]);
        $presenter = $this->presenter_builder->build($semantic_status, $this->tracker, $this->csrf_token);

        self::assertEquals(
            new AdminPresenter(
                0,
                'Status',
                '/plugins/tracker/?tracker=20&func=admin-semantic&semantic=status',
                $this->csrf_token,
                true,
                [new PossibleFieldsForStatusPresenter(1, 'field A', false), new PossibleFieldsForStatusPresenter(2, 'field B', false)],
                false,
                [],
                TRACKER_BASE_URL . '/?tracker=20&func=admin-semantic',
                0,
                null
            ),
            $presenter
        );
    }

    public function testItBuildsAListOfOpenValuesWhenStatusFieldIsDefined(): void
    {
        $status_field = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $status_field->method('getId')->willReturn(2);
        $status_field->method('getLabel')->willReturn('field B');
        $open_value = ListStaticValueBuilder::aStaticValue('open')->withId(1)->build();
        $status_field->method('getAllVisibleValues')->willReturn(
            [
                $open_value,
                ListStaticValueBuilder::aStaticValue('closed')->withId(2)->build(),
            ]
        );
        $field_A = $this->getFieldWithIdAndLabel(1, 'field A');
        $field_B = $status_field;
        $this->form_element_factory->method('getUsedListFields')->willReturn([$field_A, $field_B]);
        $semantic_status = new \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus($this->tracker, $field_B, [1]);
        $this->dao->method('getSelectedValues')->willReturn([['value_id' => 2]]);
        $presenter = $this->presenter_builder->build($semantic_status, $this->tracker, $this->csrf_token);

        self::assertEquals(
            new AdminPresenter(
                2,
                'Status',
                '/plugins/tracker/?tracker=20&func=admin-semantic&semantic=status',
                $this->csrf_token,
                true,
                [new PossibleFieldsForStatusPresenter(1, 'field A', false), new PossibleFieldsForStatusPresenter(2, 'field B', true)],
                true,
                [new StatusValuePresenter(1, 'open', true, false), new StatusValuePresenter(2, 'closed', false, true)],
                TRACKER_BASE_URL . '/?tracker=20&func=admin-semantic',
                true,
                $field_B->getLabel()
            ),
            $presenter
        );
    }

    private function getFieldWithIdAndLabel(int $id, $label): \Tracker_FormElement_Field_Selectbox
    {
        return new \Tracker_FormElement_Field_Selectbox(
            $id,
            $this->tracker->getId(),
            1,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }
}
