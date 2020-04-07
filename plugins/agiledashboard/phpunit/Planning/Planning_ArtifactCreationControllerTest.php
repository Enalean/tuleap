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

final class Planning_ArtifactCreationControllerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\GlobalResponseMock;

    /**
     * @var int
     */
    private $planning_tracker_id;
    /**
     * @var Planning
     */
    private $planning;

    /**
     * @var Planning_ArtifactCreationController
     */
    private $controller;

    protected function setUp(): void
    {
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR . '/../../..');

        $planning_id               = "99876387";
        $this->planning_tracker_id = 66;
        $request                   = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('get')->withArgs(['planning_id'])->andReturn($planning_id);

        $this->planning = Mockery::mock(Planning::class);
        $this->planning->shouldReceive('getId')->andReturn($planning_id);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturn($this->planning_tracker_id);

        $planning_factory = \Mockery::spy(\PlanningFactory::class);

        $planning_factory->shouldReceive('getPlanning')->with($planning_id)->andReturns($this->planning);

        $this->controller = new Planning_ArtifactCreationController($planning_factory, $request);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Response']);
        ForgeConfig::restore();
    }

    public function testItRedirectsToArtifactCreationForm(): void
    {
        $new_artifact_url = TRACKER_BASE_URL . "/?tracker=$this->planning_tracker_id&func=new-artifact&planning[{$this->planning->getId()}]=-1";

        $GLOBALS['Response']->shouldReceive('redirect')->once()->withArgs([$new_artifact_url]);
        $this->controller->createArtifact();
    }
}
