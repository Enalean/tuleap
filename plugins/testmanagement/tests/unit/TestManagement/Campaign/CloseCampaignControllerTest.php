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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

class CloseCampaignControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CloseCampaignController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CampaignRetriever
     */
    private $campaign_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|StatusUpdater
     */
    private $status_updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaign_retriever = Mockery::mock(CampaignRetriever::class);
        $this->status_updater     = Mockery::mock(StatusUpdater::class);

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
        $user    = Mockery::mock(PFUser::class);
        $request = new HTTPRequest();
        $request->setCurrentUser($user);

        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'campaign_id' => '3',
        ];

        $project          = new \Project(['group_id' => 101]);
        $tracker_campaign = Mockery::mock(Tracker::class);
        $tracker_campaign->shouldReceive('getProject')->andReturn($project);

        $artifact_campaign = Mockery::mock(Artifact::class);
        $artifact_campaign->shouldReceive('getTracker')->andReturn($tracker_campaign);

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );
        $this->campaign_retriever->shouldReceive('getById')
            ->once()
            ->with(3)
            ->andReturn($campaign);

        $this->status_updater->shouldReceive('closeCampaign')
            ->once();

        $layout->shouldReceive('addFeedback')->once();
        $layout->shouldReceive('redirect')->once();

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItThrowsAnExceptionIfCampaignNotFound(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $request = new HTTPRequest();
        $request->setCurrentUser($user);

        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'campaign_id' => '3',
        ];

        $this->campaign_retriever->shouldReceive('getById')
            ->once()
            ->with(3)
            ->andThrow(
                new ArtifactNotFoundException()
            );

        $this->status_updater->shouldNotReceive('closeCampaign');
        $layout->shouldNotReceive('addFeedback');
        $layout->shouldNotReceive('redirect');

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testCloseCampaignShowsAnErrorFeedbackIfNotValidValueFound(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $request = new HTTPRequest();
        $request->setCurrentUser($user);

        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'campaign_id' => '3',
        ];

        $project          = new \Project(['group_id' => 101]);
        $tracker_campaign = Mockery::mock(Tracker::class);
        $tracker_campaign->shouldReceive('getProject')->andReturn($project);

        $artifact_campaign = Mockery::mock(Artifact::class);
        $artifact_campaign->shouldReceive('getTracker')->andReturn($tracker_campaign);

        $campaign = new Campaign(
            $artifact_campaign,
            'Campaign 01',
            new NoJobConfiguration()
        );
        $this->campaign_retriever->shouldReceive('getById')
            ->once()
            ->with(3)
            ->andReturn($campaign);

        $this->status_updater->shouldReceive('closeCampaign')
            ->andThrow(NoPossibleValueException::class);

        $layout->shouldReceive('addFeedback')->withArgs(["error", "The campaign cannot be closed : No possible value found regarding your configuration. Please check your transition and field dependencies."])->once();
        $layout->shouldReceive('redirect')->once();

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }
}
