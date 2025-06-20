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

use HTTPRequest;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OpenCampaignControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private OpenCampaignController $controller;
    private CampaignRetriever&MockObject $campaign_retriever;
    private StatusUpdater&MockObject $status_updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaign_retriever = $this->createMock(CampaignRetriever::class);
        $this->status_updater     = $this->createMock(StatusUpdater::class);

        $this->controller = new OpenCampaignController(
            $this->campaign_retriever,
            $this->status_updater,
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);

        parent::tearDown();
    }

    public function testItAsksToOpenTheCampaign(): void
    {
        $user    = $this->createMock(PFUser::class);
        $request = new HTTPRequest();
        $request->setCurrentUser($user);

        $layout    = $this->createMock(BaseLayout::class);
        $variables = [
            'campaign_id' => '3',
        ];

        $project          = new \Project(['group_id' => 101]);
        $tracker_campaign = $this->createMock(Tracker::class);
        $tracker_campaign->method('getProject')->willReturn($project);

        $artifact_campaign = $this->createMock(Artifact::class);
        $artifact_campaign->method('getTracker')->willReturn($tracker_campaign);

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

        $this->status_updater->expects($this->once())->method('openCampaign');

        $layout->expects($this->once())->method('addFeedback');
        $layout->expects($this->once())->method('redirect');

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItDisplayErrorFeedbackIfNoPossibleValueToOpenTheCampaign(): void
    {
        $user    = $this->createMock(PFUser::class);
        $request = new HTTPRequest();
        $request->setCurrentUser($user);

        $layout    = $this->createMock(BaseLayout::class);
        $variables = [
            'campaign_id' => '3',
        ];

        $project          = new \Project(['group_id' => 101]);
        $tracker_campaign = $this->createMock(Tracker::class);
        $tracker_campaign->method('getProject')->willReturn($project);

        $artifact_campaign = $this->createMock(Artifact::class);
        $artifact_campaign->method('getTracker')->willReturn($tracker_campaign);

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

        $this->status_updater->method('openCampaign')->willThrowException(new NoPossibleValueException());

        $layout->expects($this->once())->method('addFeedback')->with('error', 'The campaign cannot be open : No possible value found regarding your configuration. Please check your transition and field dependencies.');
        $layout->expects($this->once())->method('redirect');

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItThrowsAnExceptionIfCampaignNotFound(): void
    {
        $user    = $this->createMock(PFUser::class);
        $request = new HTTPRequest();
        $request->setCurrentUser($user);

        $layout    = $this->createMock(BaseLayout::class);
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

        $this->status_updater->expects($this->never())->method('openCampaign');
        $layout->expects($this->never())->method('addFeedback');
        $layout->expects($this->never())->method('redirect');

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }
}
