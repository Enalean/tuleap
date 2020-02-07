<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
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

final class Planning_ArtifactParentsSelectorEventListenerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;
    /**
     * @var Planning_ArtifactParentsSelectorEventListener
     */
    private $event_listener;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_ArtifactParentsSelector
     */
    private $selector;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $epic2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $epic;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $sprint;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Codendi_Request|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $request;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $story_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $epic_tracker;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $sprint_tracker;
    protected $sprint_id = 9001;
    protected $epic_id = 2;
    protected $epic2_id = 3;

    protected function setUp(): void
    {
        '
                              epic
         ┕ sprint ───────≫   ┕ story
        ';

        $this->sprint_tracker = Mockery::mock(Tracker::class);
        $this->sprint_tracker->shouldReceive('getName')->andReturn('sprint_tracker');
        $this->epic_tracker = Mockery::mock(Tracker::class);
        $this->epic_tracker->shouldReceive('getName')->andReturn('epic_tracker');
        $this->story_tracker = Mockery::mock(Tracker::class);
        $this->story_tracker->shouldReceive('getName')->andReturn('story_tracker');

        $this->user    = Mockery::mock(PFUser::class);
        $this->request = \Mockery::spy(\Codendi_Request::class);

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $this->sprint = $this->getArtifact($this->sprint_id, $this->sprint_tracker);
        $this->epic   = $this->getArtifact($this->epic_id, $this->epic_tracker);
        $this->epic2  = $this->getArtifact($this->epic2_id, $this->epic_tracker);

        $GLOBALS['Language']->shouldReceive('getText')
            ->withArgs(['plugin_agiledashboard', 'available', 'epic_tracker'])
            ->andReturn('Available epic_tracker');

        $this->selector = \Mockery::spy(\Planning_ArtifactParentsSelector::class);
        $this->selector->shouldReceive('getPossibleParents')->with(
            $this->epic_tracker,
            $this->sprint,
            $this->user
        )->andReturns([$this->epic, $this->epic2]);
        $this->selector->shouldReceive('getPossibleParents')->with(
            $this->epic_tracker,
            $this->epic2,
            $this->user
        )->andReturns([$this->epic2]);

        $this->event_listener = new Planning_ArtifactParentsSelectorEventListener(
            $this->artifact_factory,
            $this->selector,
            $this->request
        );
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private function getArtifact($id, Tracker $tracker)
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact_factory->shouldReceive('getArtifactById')->with($id)->andReturns($artifact);

        return $artifact;
    }

    public function testItRetrievesThePossibleParentsForANewArtifactLink(): void
    {
        $label            = '';
        $possible_parents = '';
        $display_selector = true;
        $params           = [
            'user'             => $this->user,
            'parent_tracker'   => $this->epic_tracker,
            'label'            => &$label,
            'possible_parents' => &$possible_parents,
            'display_selector' => &$display_selector,
        ];
        $this->request->shouldReceive('get')->with('func')->andReturns('new-artifact-link');
        $this->request->shouldReceive('get')->with('id')->andReturns($this->sprint_id);

        $this->event_listener->process($params);

        $this->assertEquals('Available epic_tracker', $label);
        $this->assertEquals([$this->epic, $this->epic2], $possible_parents);
        $this->assertTrue($display_selector);
    }

    public function testItRetrievesThePossibleParentsForChildMilestone(): void
    {
        $label            = '';
        $possible_parents = '';
        $display_selector = true;
        $params           = array(
            'user'             => $this->user,
            'parent_tracker'   => $this->epic_tracker,
            'label'            => &$label,
            'possible_parents' => &$possible_parents,
            'display_selector' => &$display_selector,
        );
        $this->request->shouldReceive('get')->with('func')->andReturns('new-artifact');
        $this->request->shouldReceive('get')->with('child_milestone')->andReturns($this->sprint_id);

        $this->event_listener->process($params);

        $this->assertEquals('Available epic_tracker', $label);
        $this->assertEquals(array($this->epic, $this->epic2), $possible_parents);
        $this->assertTrue($display_selector);
    }

    public function testItRetrievesNothingIfThereIsNoChildMilestoneNorNewArtifactLink(): void
    {
        $label            = 'untouched';
        $possible_parents = 'untouched';
        $display_selector = 'untouched';
        $params           = array(
            'user'             => $this->user,
            'parent_tracker'   => $this->epic_tracker,
            'label'            => &$label,
            'possible_parents' => &$possible_parents,
            'display_selector' => &$display_selector,
        );
        $this->request->shouldReceive('get')->andReturns(false);

        $this->event_listener->process($params);

        $this->assertEquals('untouched', $label);
        $this->assertEquals('untouched', $possible_parents);
        $this->assertEquals('untouched', $display_selector);
    }

    public function testItAsksForNoSelectorIfWeLinkToAParent(): void
    {
        $label            = '';
        $possible_parents = '';
        $display_selector = true;
        $params           = array(
            'user'             => $this->user,
            'parent_tracker'   => $this->epic_tracker,
            'label'            => &$label,
            'possible_parents' => &$possible_parents,
            'display_selector' => &$display_selector,
        );
        $this->request->shouldReceive('get')->with('func')->andReturns('new-artifact');
        $this->request->shouldReceive('get')->with('child_milestone')->andReturns($this->epic2_id);

        $this->event_listener->process($params);

        $this->assertEquals(array($this->epic2), $possible_parents);
        $this->assertFalse($display_selector);
    }
}
