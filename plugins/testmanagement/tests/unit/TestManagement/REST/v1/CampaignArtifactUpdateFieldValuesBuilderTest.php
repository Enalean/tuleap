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
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CampaignArtifactUpdateFieldValuesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CampaignArtifactUpdateFieldValuesBuilder $builder;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private StatusValueRetriever&MockObject $status_value_retriever;
    private Campaign&MockObject $campaign;
    private RetrieveSemanticStatusFieldStub $status_field_retriever;

    protected function setUp(): void
    {
        $this->formelement_factory    = $this->createMock(Tracker_FormElementFactory::class);
        $this->status_value_retriever = $this->createMock(StatusValueRetriever::class);
        $this->status_field_retriever = RetrieveSemanticStatusFieldStub::build();

        $this->campaign = $this->createMock(Campaign::class);
        $this->campaign->method('getLabel')->willReturn('new_label');
        $this->campaign->method('getArtifact')->willReturn(ArtifactTestBuilder::anArtifact(112)->build());

        $this->builder = new CampaignArtifactUpdateFieldValuesBuilder(
            $this->formelement_factory,
            $this->status_value_retriever,
            $this->status_field_retriever,
        );
    }

    public function testItBuildsFieldValueForLabel(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::aUser()->build();

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

        $this->status_field_retriever->withField(ListFieldBuilder::aListField(98)->inTracker($tracker)->build());

        $this->status_value_retriever
            ->expects($this->once())
            ->method('getFirstClosedValueUserCanRead')
            ->willReturn(
                ListStaticValueBuilder::aStaticValue('done')->withId(5)->build()
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

        $this->status_field_retriever->withField(ListFieldBuilder::aListField(98)->inTracker($tracker)->build());

        $this->status_value_retriever
            ->expects($this->once())
            ->method('getFirstOpenValueUserCanRead')
            ->willReturn(
                ListStaticValueBuilder::aStaticValue('on going')->withId(2)->build()
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
            ->expects($this->once())
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
