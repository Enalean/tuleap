<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use AgileDashBoard_Semantic_InitialEffort;
use CSRFSynchronizerToken;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class InitialEffortSemanticAdminPresenterBuilderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SemanticInitialEffortPossibleFieldRetriever|(SemanticInitialEffortPossibleFieldRetriever&\PHPUnit\Framework\MockObject\MockObject)
     */
    private \PHPUnit\Framework\MockObject\MockObject|SemanticInitialEffortPossibleFieldRetriever $possible_fields_retriever;
    /**
     * @var CSRFSynchronizerToken|(CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject|CSRFSynchronizerToken $csrf_token;
    private \Tracker $tracker;
    private InitialEffortSemanticAdminPresenterBuilder $builder;

    protected function setUp(): void
    {
        $this->possible_fields_retriever = $this->createMock(SemanticInitialEffortPossibleFieldRetriever::class);
        $this->tracker                   = TrackerTestBuilder::aTracker()->withId(5)->build();
        $this->builder                   = new InitialEffortSemanticAdminPresenterBuilder($this->possible_fields_retriever);
        $this->csrf_token                = $this->createMock(CSRFSynchronizerToken::class);
    }

    public function testItBuildsASemanticPresenter(): void
    {
        $field_id = 102;

        $initial_effort_semantic = $this->createMock(AgileDashBoard_Semantic_InitialEffort::class);
        $initial_effort_semantic->method('getTracker')->willReturn($this->tracker);
        $initial_effort_semantic->method('getFieldId')->willReturn($field_id);
        $initial_effort_semantic->method('getLabel')->willReturn("Initial effort");
        $initial_effort_semantic->method('getUrl')->willReturn("/func=admin-semantic&semantic=initial_effort");

        $this->possible_fields_retriever->method('getPossibleFieldsForInitialEffort')->willReturn(
            [
                $this->getFieldWithLabel(1, "field_a"),
                $this->getFieldWithLabel(2, "field_b"),
            ]
        );
        $presenter = $this->builder->build($initial_effort_semantic, $this->csrf_token);
        self::assertEquals(
            new InitialEffortAdminSemanticPresenter(
                $this->csrf_token,
                TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId() . '&func=admin-semantic',
                PossibleFieldsPresenter::buildFromTrackerFieldList(
                    [
                        $this->getFieldWithLabel(1, "field_a"),
                        $this->getFieldWithLabel(2, "field_b"),
                    ],
                    $initial_effort_semantic
                ),
                "/func=admin-semantic&semantic=initial_effort",
                true
            ),
            $presenter
        );
    }

    private function getFieldWithLabel(int $id, string $label): \Tracker_FormElement_Field_Integer
    {
        return new \Tracker_FormElement_Field_Integer(
            $id,
            $this->tracker->getId(),
            0,
            $label,
            $label,
            '',
            true,
            'P',
            false,
            false,
            1
        );
    }
}
