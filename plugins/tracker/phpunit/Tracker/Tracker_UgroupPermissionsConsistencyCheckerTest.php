<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_UgroupPermissionsConsistencyCheckerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $template_tracker;
    private $target_project;
    private $permissions_retriever;
    private $ugroup_manager;
    private $template_ugroup_dev;
    private $template_ugroup_support;
    private $target_ugroup_dev;
    private $target_ugroup_support;

    /**
     * @var Tracker_UgroupPermissionsConsistencyChecker
     */
    protected $checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_UgroupPermissionsConsistencyMessenger
     */
    private $messenger;

    protected function setUp(): void
    {
        $this->template_ugroup_dev     = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('dev')->getMock();
        $this->template_ugroup_support = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('support')->getMock();
        $this->target_ugroup_dev       = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('dev')->getMock();
        $this->target_ugroup_support   = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('support')->getMock();

        $template_project            = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(103)->getMock();
        $this->template_tracker      = Mockery::mock(Tracker::class)->shouldReceive('getProject')->andReturn($template_project)->getMock();
        $this->target_project        = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(104)->getMock();
        $this->ugroup_manager        = \Mockery::spy(\UGroupManager::class);
        $this->messenger             = \Mockery::spy(\Tracker_UgroupPermissionsConsistencyMessenger::class);
        $this->permissions_retriever = \Mockery::spy(\Tracker_UgroupPermissionsGoldenRetriever::class);

        $this->checker = new Tracker_UgroupPermissionsConsistencyChecker(
            $this->permissions_retriever,
            $this->ugroup_manager,
            $this->messenger
        );
    }

    public function testItReturnsNoMessageSameProject(): void
    {
        $this->messenger->shouldReceive('allIsWell')->once();
        $this->messenger->shouldReceive('ugroupsMissing')->never();
        $this->messenger->shouldReceive('ugroupsAreTheSame')->never();

        $this->checker->checkConsistency($this->template_tracker, $this->template_tracker->getProject());
    }

    public function testItReturnsNoMessageNoPermOnStaticGroups(): void
    {
        $this->permissions_retriever->shouldReceive('getListOfInvolvedStaticUgroups')->with($this->template_tracker)->andReturns([]);

        $this->messenger->shouldReceive('allIsWell')->once();
        $this->messenger->shouldReceive('ugroupsMissing')->never();
        $this->messenger->shouldReceive('ugroupsAreTheSame')->never();

        $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }

    public function testItReturnsAWarningWhenTheTargetProjectDoesNotHaveTheStaticGroup(): void
    {
        $this->permissions_retriever->shouldReceive('getListOfInvolvedStaticUgroups')->with($this->template_tracker)->andReturns(array($this->template_ugroup_dev));
        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($this->target_project)->andReturns(array($this->target_ugroup_support));

        $this->messenger->shouldReceive('allIsWell')->never();
        $this->messenger->shouldReceive('ugroupsMissing')->once();
        $this->messenger->shouldReceive('ugroupsAreTheSame')->never();

        $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }

    public function testItReturnsAnInfoWhenTheTargetProjectHasTheStaticGroup(): void
    {
        $this->permissions_retriever->shouldReceive('getListOfInvolvedStaticUgroups')->with($this->template_tracker)->andReturns(array($this->template_ugroup_dev));
        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($this->target_project)->andReturns(array($this->target_ugroup_dev));

        $this->messenger->shouldReceive('allIsWell')->never();
        $this->messenger->shouldReceive('ugroupsMissing')->never();
        $this->messenger->shouldReceive('ugroupsAreTheSame')->once();

        $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }

    public function testItReturnsAWarningWhenTheTargetProjectDoesNotHaveTheStaticGroups(): void
    {
        $this->permissions_retriever->shouldReceive('getListOfInvolvedStaticUgroups')->with($this->template_tracker)->andReturns(array($this->template_ugroup_dev, $this->template_ugroup_support));
        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($this->target_project)->andReturns(array());

        $this->messenger->shouldReceive('allIsWell')->never();
        $this->messenger->shouldReceive('ugroupsMissing')->once();
        $this->messenger->shouldReceive('ugroupsAreTheSame')->never();

        $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }

    public function testItReturnsAWarningWhenTheTargetProjectDoesNotHaveOneOfTheStaticGroups(): void
    {
        $this->permissions_retriever->shouldReceive('getListOfInvolvedStaticUgroups')->with($this->template_tracker)->andReturns(array($this->template_ugroup_dev, $this->template_ugroup_support));
        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($this->target_project)->andReturns(array($this->target_ugroup_dev));

        $this->messenger->shouldReceive('allIsWell')->never();
        $this->messenger->shouldReceive('ugroupsMissing')->once();
        $this->messenger->shouldReceive('ugroupsAreTheSame')->never();

        $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }

    public function testItReturnsAnInfoWhenTheTargetProjectHasTheStaticGroups(): void
    {
        $this->permissions_retriever->shouldReceive('getListOfInvolvedStaticUgroups')->with($this->template_tracker)->andReturns(array($this->template_ugroup_dev, $this->template_ugroup_support));
        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($this->target_project)->andReturns(array($this->target_ugroup_dev, $this->target_ugroup_support));

        $this->messenger->shouldReceive('allIsWell')->never();
        $this->messenger->shouldReceive('ugroupsMissing')->never();
        $this->messenger->shouldReceive('ugroupsAreTheSame')->once();

        $this->checker->checkConsistency($this->template_tracker, $this->target_project);
    }
}
