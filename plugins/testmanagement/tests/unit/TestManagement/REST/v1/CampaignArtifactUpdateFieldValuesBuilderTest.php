<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElementFactory;
use Tracker_Semantic_Status;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;

final class CampaignArtifactUpdateFieldValuesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CampaignArtifactUpdateFieldValuesBuilder $builder;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private StatusValueRetriever&MockObject $status_value_retriever;
    private Campaign&MockObject $campaign;

    protected function setUp(): void
    {
        $this->formelement_factory    = $this->createMock(Tracker_FormElementFactory::class);
        $this->status_value_retriever = $this->createMock(StatusValueRetriever::class);

        $this->campaign = $this->createMock(Campaign::class);
        $this->campaign->method('getLabel')->willReturn('new_label');
        $this->campaign->method('getArtifact')->willReturn(ArtifactTestBuilder::anArtifact(112)->build());

        $this->builder = new CampaignArtifactUpdateFieldValuesBuilder(
            $this->formelement_factory,
            $this->status_value_retriever
        );
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Status::clearInstances();
    }

    public function testItBuildsFieldValueForLabel(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::aUser()->build();

        \Tracker_Semantic_Status::setInstance(
            new Tracker_Semantic_Status($tracker, null),
            $tracker,
        );

        $this->formelement_factory
            ->method('getUsedFieldByNameForUser')
            ->willReturn(StringFieldBuilder::aStringField(89)->build());

        $field_values = $this->builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $this->campaign,
            null
        );

        assertCount(1, $field_values);
        assertSame('new_label', $field_values[0]->value);
        assertSame(89, $field_values[0]->field_id);
    }

    public function testItBuildsFieldValueForLabelAndStatusToBeClosed(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::aUser()->build();

        \Tracker_Semantic_Status::setInstance(
            new Tracker_Semantic_Status($tracker, ListFieldBuilder::aListField(98)->build()),
            $tracker,
        );

        $this->status_value_retriever
            ->expects(self::once())
            ->method('getFirstClosedValueUserCanRead')
            ->willReturn(
                new Tracker_FormElement_Field_List_Bind_StaticValue(
                    5,
                    'done',
                    '',
                    4,
                    false
                )
            );

        $this->formelement_factory
            ->method('getUsedFieldByNameForUser')
            ->willReturn(StringFieldBuilder::aStringField(89)->build());

        $field_values = $this->builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $this->campaign,
            'closed'
        );

        assertCount(2, $field_values);
        assertSame('new_label', $field_values[0]->value);
        assertSame(89, $field_values[0]->field_id);
        assertSame([5], $field_values[1]->bind_value_ids);
        assertSame(98, $field_values[1]->field_id);
    }

    public function testItBuildsFieldValueForLabelAndStatusToBeOpen(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::aUser()->build();

        \Tracker_Semantic_Status::setInstance(
            new Tracker_Semantic_Status($tracker, ListFieldBuilder::aListField(98)->build()),
            $tracker,
        );

        $this->status_value_retriever
            ->expects(self::once())
            ->method('getFirstOpenValueUserCanRead')
            ->willReturn(
                new Tracker_FormElement_Field_List_Bind_StaticValue(
                    2,
                    'on going',
                    '',
                    1,
                    false
                )
            );

        $this->formelement_factory
            ->method('getUsedFieldByNameForUser')
            ->willReturn(StringFieldBuilder::aStringField(89)->build());

        $field_values = $this->builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $this->campaign,
            'open'
        );

        assertCount(2, $field_values);
        assertSame('new_label', $field_values[0]->value);
        assertSame(89, $field_values[0]->field_id);
        assertSame([2], $field_values[1]->bind_value_ids);
        assertSame(98, $field_values[1]->field_id);
    }

    public function testItThrowsAnExceptionIfNoLabelFieldConfigured(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::aUser()->build();

        $this->formelement_factory
            ->expects(self::once())
            ->method('getUsedFieldByNameForUser')
            ->willReturn(null);

        $this->expectException(LabelFieldNotFoundException::class);

        $this->builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $this->campaign,
            null
        );
    }
}
