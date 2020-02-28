<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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


final class Planning_MilestoneSelectorControllerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    use \Tuleap\ForgeConfigSandbox, \Tuleap\GlobalResponseMock;
    /**
     * @var int
     */
    private $current_milestone_artifact_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Codendi_Request
     */
    private $request;

    protected function setUp(): void
    {
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR . '/../../..');
        $planning_id = '321';
        $user = Mockery::mock(PFUser::class);
        $this->request = Mockery::mock(Codendi_Request::class);
        $this->request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->request->shouldReceive('getValidated')->andReturn($planning_id);
        $this->milestone_factory = \Mockery::spy(\Planning_MilestoneFactory::class);

        $this->current_milestone_artifact_id = 444;

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($this->current_milestone_artifact_id);
        $milestone = Mockery::mock(Planning_ArtifactMilestone::class);
        $milestone->shouldReceive('getArtifact')->andReturn($artifact);
        $milestone->shouldReceive('getGroupId')->andReturn(101);
        $milestone->shouldReceive('getPlanningId')->andReturn($planning_id);

        $this->milestone_factory->shouldReceive('getLastMilestoneCreated')
            ->with($user, $planning_id)->andReturns($milestone);

        $GLOBALS['Response'] = Mockery::spy(Layout::class);
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        unset($GLOBALS['Response']);
    }

    public function testItRedirectToTheCurrentMilestone(): void
    {
        $GLOBALS['Response']->shouldReceive('redirect')
            ->with(\Mockery::pattern("/aid=$this->current_milestone_artifact_id/"))
            ->once();
        $controller = new Planning_MilestoneSelectorController($this->request, $this->milestone_factory);
        $controller->show();
    }

    public function testItRedirectToTheCurrentMilestoneCardwallIfAny(): void
    {
        $event_manager = \Mockery::mock(\EventManager::class);
        EventManager::setInstance($event_manager);

        $event_manager->shouldReceive('processEvent')->with(
            AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT,
            \Mockery::any()
        );

        $controller = new Planning_MilestoneSelectorController($this->request, $this->milestone_factory);
        $controller->show();
    }

    public function testItDoesntRedirectIfNoMilestone(): void
    {
        $milestone_factory = \Mockery::spy(\Planning_MilestoneFactory::class);
        $milestone_factory->shouldReceive('getLastMilestoneCreated')->andReturns(
            \Mockery::spy(\Planning_NoMilestone::class)
        );

        $GLOBALS['Response']->shouldReceive('redirect')->never();
        $controller = new Planning_MilestoneSelectorController($this->request, $milestone_factory);
        $controller->show();
    }
}
