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

final class Planning_RequestValidatorTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var Planning
     */
    private $release_planning;
    /**
     * @var int
     */
    private $holidays_tracker_id;
    /**
     * @var int
     */
    private $sprints_tracker_id;
    /**
     * @var int
     */
    private $releases_tracker_id;
    /**
     * @var int
     */
    private $release_planning_id;

    /**
     * @var Planning_RequestValidator
     */
    private $validator;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanningFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory   = \Mockery::spy(\PlanningFactory::class);
        $this->validator = new Planning_RequestValidator($this->factory);

        $this->release_planning_id = 34;
        $this->releases_tracker_id = 56;
        $this->sprints_tracker_id  = 78;
        $this->holidays_tracker_id = 90;
    }

    public function testItRejectsTheRequestWhenNameIsMissing(): void
    {
        $request = $this->getPlanningRequest(null, 1, 2, null);
        $this->assertFalse($this->validator->isValid($request));
    }

    public function testItRejectsTheRequestWhenBacklogTrackerIdsAreMissing(): void
    {
        $request = $this->getPlanningRequest("test", 1, null, null);
        $this->assertFalse($this->validator->isValid($request));
    }

    public function testItRejectsTheRequestWhenPlanningTrackerIdIsMissing(): void
    {
        $request = $this->getPlanningRequest("test", null, 2, null);
        $this->assertFalse($this->validator->isValid($request));
    }

    private function getPlanningRequest(
        ?string $planning_name,
        ?int $planning_tracker_id,
        ?int $backlog_tracker_id,
        ?int $planning_id
    ): Codendi_Request {
        $planning = [
            'name'                                  => 'My Planning',
            'planning_tracker_id'                   => '1',
            PlanningParameters::BACKLOG_TRACKER_IDS => ['2']
        ];

        $planning['planning_tracker_id']                     = $planning_tracker_id;
        $planning[PlanningParameters::BACKLOG_TRACKER_IDS][] = $backlog_tracker_id;
        $planning['name']                                    = $planning_name;

        return new Codendi_Request(
            [
                'group_id'    => 12,
                'planning_id' => $planning_id,
                'planning'    => $planning
            ]
        );
    }

    public function testItValidatesTheRequestWhenPlanningTrackerIsNotUsedInAPlanningOfTheSameProject(): void
    {
        $this->getAReleaseWithPlanning();
        $request = $this->getPlanningRequest("test", $this->holidays_tracker_id, 1, $this->release_planning_id);

        $this->assertTrue($this->validator->isValid($request));
    }

    public function testItValidatesTheRequestWhenPlanningTrackerIsTheCurrentOne(): void
    {
        $this->getAReleaseWithPlanning();
        $request = $this->getPlanningRequest("test", $this->releases_tracker_id, 2, $this->release_planning_id);

        $this->assertTrue($this->validator->isValid($request));
    }

    public function testItRejectsTheRequestWhenPlanningTrackerIsUsedInAPlanningOfTheSameProject(): void
    {
        $this->getAReleaseWithPlanning();
        $request = $this->getPlanningRequest("test", $this->sprints_tracker_id, null, $this->release_planning_id);

        $this->assertFalse($this->validator->isValid($request));
    }

    private function getAReleaseWithPlanning(): void
    {
        $group_id = 12;

        $this->release_planning = new Planning(
            $this->release_planning_id,
            "test",
            $group_id,
            "backlog title",
            "planning_title",
            [],
            $this->releases_tracker_id
        );

        $this->factory->shouldReceive('getPlanning')->with($this->release_planning_id)->andReturns(
            $this->release_planning
        );
        $this->factory->shouldReceive('getPlanningTrackerIdsByGroupId')->with($group_id)->andReturns(
            [
                $this->releases_tracker_id,
                $this->sprints_tracker_id
            ]
        );
    }
}
