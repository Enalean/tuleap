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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_String;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;

class CampaignArtifactUpdateFieldValuesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CampaignArtifactUpdateFieldValuesBuilder
     */
    private $builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|StatusValueRetriever
     */
    private $status_value_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formelement_factory    = Mockery::mock(Tracker_FormElementFactory::class);
        $this->status_value_retriever = Mockery::mock(StatusValueRetriever::class);

        $this->campaign = Mockery::mock(Campaign::class);
        $this->campaign->shouldReceive('getLabel')->andReturn("new_label");
        $this->campaign->shouldReceive('getArtifact')->andReturn(ArtifactTestBuilder::anArtifact(112)->build());

        $this->builder = new CampaignArtifactUpdateFieldValuesBuilder(
            $this->formelement_factory,
            $this->status_value_retriever
        );
    }

    public function testItBuildsFieldValueForLabel(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $user    = UserTestBuilder::aUser()->build();

        $tracker->shouldReceive('getId')->andReturn(47);
        $tracker->shouldNotReceive('getStatusField');
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_String(
                    89,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null
                )
            );

        $field_values = $this->builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $this->campaign,
            null
        );

        assertCount(1, $field_values);
        assertSame("new_label", $field_values[0]->value);
        assertSame(89, $field_values[0]->field_id);
    }

    public function testItBuildsFieldValueForLabelAndStatusToBeClosed(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $user    = UserTestBuilder::aUser()->build();

        $tracker->shouldReceive('getId')->andReturn(47);
        $tracker->shouldReceive('getStatusField')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_Selectbox(
                    98,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null
                )
            );

        $this->status_value_retriever->shouldReceive('getFirstClosedValueUserCanRead')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_List_Bind_StaticValue(
                    5,
                    'done',
                    '',
                    4,
                    false
                )
            );

        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_String(
                    89,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null
                )
            );

        $field_values = $this->builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $this->campaign,
            'closed'
        );

        assertCount(2, $field_values);
        assertSame("new_label", $field_values[0]->value);
        assertSame(89, $field_values[0]->field_id);
        assertSame([5], $field_values[1]->bind_value_ids);
        assertSame(98, $field_values[1]->field_id);
    }

    public function testItBuildsFieldValueForLabelAndStatusToBeOpen(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $user    = UserTestBuilder::aUser()->build();

        $tracker->shouldReceive('getId')->andReturn(47);
        $tracker->shouldReceive('getStatusField')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_Selectbox(
                    98,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null
                )
            );

        $this->status_value_retriever->shouldReceive('getFirstOpenValueUserCanRead')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_List_Bind_StaticValue(
                    2,
                    'on going',
                    '',
                    1,
                    false
                )
            );

        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_String(
                    89,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null
                )
            );

        $field_values = $this->builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $this->campaign,
            'open'
        );

        assertCount(2, $field_values);
        assertSame("new_label", $field_values[0]->value);
        assertSame(89, $field_values[0]->field_id);
        assertSame([2], $field_values[1]->bind_value_ids);
        assertSame(98, $field_values[1]->field_id);
    }

    public function testItThrowsAnExceptionIfNoLabelFieldConfigured(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $user    = UserTestBuilder::aUser()->build();

        $tracker->shouldReceive('getId')->andReturn(47);
        $tracker->shouldReceive('getName')->andReturn('tracker01');
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')
            ->once()
            ->andReturnNull();

        self::expectException(LabelFieldNotFoundException::class);

        $this->builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $this->campaign,
            null
        );
    }
}
