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
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private StatusUpdater $updater;
    private StatusValueRetriever&MockObject $status_value_retriever;

    protected function setUp(): void
    {
        $this->status_value_retriever = $this->createMock(StatusValueRetriever::class);

        $this->updater = new StatusUpdater($this->status_value_retriever);
    }

    public function testItOpensACampaign(): void
    {
        $user       = UserTestBuilder::anActiveUser()->build();
        $csrf_token = $this->createMock(CSRFSynchronizerToken::class);

        $status_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $status_field
            ->expects(self::once())
            ->method('getId')
            ->willReturn(156);
        $status_field
            ->expects(self::once())
            ->method('getFieldData')
            ->willReturn(1);

        $tracker_campaign = $this->createMock(Tracker::class);
        $tracker_campaign
            ->expects(self::once())
            ->method('getStatusField')
            ->willReturn($status_field);

        $artifact_campaign = $this->createMock(Artifact::class);
        $artifact_campaign->method('getTracker')->willReturn($tracker_campaign);

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );

        $csrf_token->expects(self::once())->method('check');

        $this->status_value_retriever
            ->expects(self::once())
            ->method('getFirstOpenValueUserCanRead')
            ->willReturn(
                ListStaticValueBuilder::aStaticValue('open')->withId(1)->build()
            );

        $artifact_campaign
            ->expects(self::once())
            ->method('createNewChangeset')
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
        $csrf_token = $this->createMock(CSRFSynchronizerToken::class);

        $status_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $status_field
            ->expects(self::once())
            ->method('getId')
            ->willReturn(156);
        $status_field
            ->expects(self::once())
            ->method('getFieldData')
            ->willReturn(2);

        $tracker_campaign = $this->createMock(Tracker::class);
        $tracker_campaign
            ->expects(self::once())
            ->method('getStatusField')
            ->willReturn($status_field);

        $artifact_campaign = $this->createMock(Artifact::class);
        $artifact_campaign->method('getTracker')->willReturn($tracker_campaign);

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );

        $csrf_token->expects(self::once())->method('check');

        $this->status_value_retriever
            ->expects(self::once())
            ->method('getFirstClosedValueUserCanRead')
            ->willReturn(
                ListStaticValueBuilder::aStaticValue('closed')->withId(2)->build()
            );

        $artifact_campaign
            ->expects(self::once())
            ->method('createNewChangeset')
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
        $csrf_token = $this->createMock(CSRFSynchronizerToken::class);

        $tracker_campaign = $this->createMock(Tracker::class);
        $tracker_campaign
            ->expects(self::once())
            ->method('getStatusField')
            ->willReturn(null);

        $artifact_campaign = $this->createMock(Artifact::class);
        $artifact_campaign->method('getTracker')->willReturn($tracker_campaign);

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );

        $csrf_token->method('check');

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
