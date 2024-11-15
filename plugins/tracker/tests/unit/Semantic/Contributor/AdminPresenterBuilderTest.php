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

namespace Tuleap\Tracker\Semantic\Contributor;

use CSRFSynchronizerToken;
use Tracker_FormElementFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AdminPresenterBuilderTest extends TestCase
{
    private Tracker_FormElementFactory|\PHPUnit\Framework\MockObject\MockObject $form_element_factory;
    private \Tracker_Semantic_Contributor $semantic_contributor;
    private CSRFSynchronizerToken $csrf_token;
    private AdminPresenterBuilder $presenter_builder;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker              = TrackerTestBuilder::aTracker()->withId(20)->build();
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);

        $this->csrf_token = $this->createMock(CSRFSynchronizerToken::class);

        $this->presenter_builder = new AdminPresenterBuilder($this->form_element_factory);
    }

    public function testItBuildsAPresenterWhenNoFieldsArePossible(): void
    {
        $semantic_contributor = new \Tracker_Semantic_Contributor($this->tracker, null);
        $this->form_element_factory->method('searchUsedUserClosedListFields')->willReturn([]);
        $presenter = $this->presenter_builder->build($semantic_contributor, $this->tracker, $this->csrf_token);

        self::assertEquals(
            new AdminPresenter(
                'Contributor/assignee',
                '/plugins/tracker/?tracker=20&func=admin-semantic&semantic=contributor',
                $this->csrf_token,
                false,
                [],
                false,
                TRACKER_BASE_URL . '/?tracker=20&func=admin-semantic'
            ),
            $presenter
        );
    }

    public function testItBuildsAPresenterWhenNoContributorIsDefined(): void
    {
        $field_A = $this->getFieldWithIdAndLabel(1, 'field A');
        $field_B = $this->getFieldWithIdAndLabel(2, 'field B');
        $this->form_element_factory->method('searchUsedUserClosedListFields')->willReturn([$field_A, $field_B]);
        $semantic_contributor = new \Tracker_Semantic_Contributor($this->tracker, null);
        $presenter            = $this->presenter_builder->build($semantic_contributor, $this->tracker, $this->csrf_token);

        self::assertEquals(
            new AdminPresenter(
                'Contributor/assignee',
                '/plugins/tracker/?tracker=20&func=admin-semantic&semantic=contributor',
                $this->csrf_token,
                false,
                [new PossibleFieldsForContributorPresenter(1, 'field A', false), new PossibleFieldsForContributorPresenter(2, 'field B', false)],
                true,
                TRACKER_BASE_URL . '/?tracker=20&func=admin-semantic'
            ),
            $presenter
        );
    }

    public function testItBuildsAPresenterWithContributorSemantic(): void
    {
        $field_A = $this->getFieldWithIdAndLabel(1, 'field A');
        $field_B = $this->getFieldWithIdAndLabel(2, 'field B');
        $this->form_element_factory->method('searchUsedUserClosedListFields')->willReturn([$field_A, $field_B]);
        $semantic_contributor = new \Tracker_Semantic_Contributor($this->tracker, $field_B);
        $presenter            = $this->presenter_builder->build($semantic_contributor, $this->tracker, $this->csrf_token);

        self::assertEquals(
            new AdminPresenter(
                'Contributor/assignee',
                '/plugins/tracker/?tracker=20&func=admin-semantic&semantic=contributor',
                $this->csrf_token,
                true,
                [new PossibleFieldsForContributorPresenter(1, 'field A', false), new PossibleFieldsForContributorPresenter(2, 'field B', true)],
                true,
                TRACKER_BASE_URL . '/?tracker=20&func=admin-semantic'
            ),
            $presenter
        );
    }

    private function getFieldWithIdAndLabel(int $id, $label): \Tracker_FormElement_Field_List
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
