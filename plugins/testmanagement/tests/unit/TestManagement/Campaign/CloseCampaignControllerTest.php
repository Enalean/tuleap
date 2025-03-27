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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CloseCampaignControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CloseCampaignController $controller;
    private CampaignRetriever&MockObject $campaign_retriever;
    private StatusUpdater&MockObject $status_updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaign_retriever = $this->createMock(CampaignRetriever::class);
        $this->status_updater     = $this->createMock(StatusUpdater::class);

        $this->controller = new CloseCampaignController(
            $this->campaign_retriever,
            $this->status_updater,
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);

        parent::tearDown();
    }

    public function testItAsksToCloseTheCampaign(): void
    {
        $request = HTTPRequestBuilder::get()->build();

        $layout    = $this->createMock(BaseLayout::class);
        $variables = [
            'campaign_id' => '3',
        ];

        $project          = ProjectTestBuilder::aProject()->build();
        $tracker_campaign = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $artifact_campaign = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker_campaign)->build();

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );
        $this->campaign_retriever
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($campaign);

        $this->status_updater
            ->expects($this->once())
            ->method('closeCampaign');

        $layout->expects($this->once())->method('addFeedback');
        $layout->expects($this->once())->method('redirect');

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItThrowsAnExceptionIfCampaignNotFound(): void
    {
        $request = HTTPRequestBuilder::get()->build();

        $inspector = new LayoutInspector();
        $layout    = new TestLayout($inspector);
        $variables = [
            'campaign_id' => '3',
        ];

        $this->campaign_retriever
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willThrowException(
                new ArtifactNotFoundException()
            );

        $this->status_updater->method('closeCampaign');

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $request,
            $layout,
            $variables
        );

        self::assertSame([], $inspector->getFeedback());
    }

    public function testCloseCampaignShowsAnErrorFeedbackIfNotValidValueFound(): void
    {
        $request = HTTPRequestBuilder::get()->build();

        $inspector = new LayoutInspector();
        $layout    = new TestLayout($inspector);
        $variables = [
            'campaign_id' => '3',
        ];

        $project          = ProjectTestBuilder::aProject()->build();
        $tracker_campaign = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $artifact_campaign = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker_campaign)->build();

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );
        $this->campaign_retriever
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($campaign);

        $this->status_updater->method('closeCampaign')
            ->willThrowException(new NoPossibleValueException());

        $redirected = false;

        try {
            $this->controller->process(
                $request,
                $layout,
                $variables
            );
        } catch (LayoutInspectorRedirection $e) {
            $redirected = true;
            self::assertSame('/plugins/testmanagement/?group_id=101#!/campaigns/3', $e->redirect_url);
        }

        self::assertTrue($redirected);
        self::assertSame(
            [[
                'level' => 'error',
                'message' => 'The campaign cannot be closed : No possible value found regarding your configuration. Please check your transition and field dependencies.',
            ],
            ],
            $inspector->getFeedback()
        );
    }
}
