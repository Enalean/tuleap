<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign;

use CSRFSynchronizerToken;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;

class StatusUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var StatusUpdater
     */
    private $updater;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|StatusValueRetriever
     */
    private $status_value_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->status_value_retriever = Mockery::mock(StatusValueRetriever::class);

        $this->updater = new StatusUpdater($this->status_value_retriever);
    }

    public function testItOpensACampaign(): void
    {
        $user       = UserTestBuilder::anActiveUser()->build();
        $csrf_token = Mockery::mock(CSRFSynchronizerToken::class);

        $status_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $status_field->shouldReceive('getId')
            ->once()
            ->andReturn(156);
        $status_field->shouldReceive('getFieldData')
            ->once()
            ->andReturn(1);

        $tracker_campaign = Mockery::mock(Tracker::class);
        $tracker_campaign->shouldReceive('getStatusField')
            ->once()
            ->andReturn($status_field);

        $artifact_campaign = Mockery::mock(Artifact::class);
        $artifact_campaign->shouldReceive('getTracker')->andReturn($tracker_campaign);

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );

        $csrf_token->shouldReceive('check')->once();

        $this->status_value_retriever->shouldReceive('getFirstOpenValueUserCanRead')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_List_Bind_StaticValue(1, 'open', '', 1, false)
            );

        $artifact_campaign->shouldReceive('createNewChangeset')
            ->once()
            ->with(
                [156 => 1],
                '',
                $user
            );

        $this->updater->openCampaign(
            $campaign,
            $user,
            $csrf_token
        );
    }

    public function testItClosesACampaign(): void
    {
        $user       = UserTestBuilder::anActiveUser()->build();
        $csrf_token = Mockery::mock(CSRFSynchronizerToken::class);

        $status_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $status_field->shouldReceive('getId')
            ->once()
            ->andReturn(156);
        $status_field->shouldReceive('getFieldData')
            ->once()
            ->andReturn(2);

        $tracker_campaign = Mockery::mock(Tracker::class);
        $tracker_campaign->shouldReceive('getStatusField')
            ->once()
            ->andReturn($status_field);

        $artifact_campaign = Mockery::mock(Artifact::class);
        $artifact_campaign->shouldReceive('getTracker')->andReturn($tracker_campaign);

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );

        $csrf_token->shouldReceive('check')->once();

        $this->status_value_retriever->shouldReceive('getFirstClosedValueUserCanRead')
            ->once()
            ->andReturn(
                new Tracker_FormElement_Field_List_Bind_StaticValue(2, 'closed', '', 2, false)
            );

        $artifact_campaign->shouldReceive('createNewChangeset')
            ->once()
            ->with(
                [156 => 2],
                '',
                $user
            );

        $this->updater->closeCampaign(
            $campaign,
            $user,
            $csrf_token
        );
    }

    public function testItThrowsAnExceptionIfStatusSemanticNotDefined(): void
    {
        $user       = UserTestBuilder::anActiveUser()->build();
        $csrf_token = Mockery::mock(CSRFSynchronizerToken::class);

        $tracker_campaign = Mockery::mock(Tracker::class);
        $tracker_campaign->shouldReceive('getStatusField')
            ->once()
            ->andReturnNull();

        $artifact_campaign = Mockery::mock(Artifact::class);
        $artifact_campaign->shouldReceive('getTracker')->andReturn($tracker_campaign);

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );

        $csrf_token->shouldReceive('check');

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->updater->closeCampaign(
            $campaign,
            $user,
            $csrf_token
        );

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->updater->openCampaign(
            $campaign,
            $user,
            $csrf_token
        );
    }
}
