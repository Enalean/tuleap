<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * A planning milestone (e.g.: Sprint, Release...)
 */
class Planning_ArtifactMilestone implements Planning_Milestone //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private const string PROMOTED_ITEM_PREFIX = 'milestone-';

    /**
     * The project where the milestone is defined
     *
     * @var Project
     */
    private $project;

    /**
     * The association between the tracker that define the "Content" (aka Backlog) (ie. Epic)
     * and the tracker that define the plan (ie. Release)
     *
     * @var Planning
     */

    private $planning;

    /**
     * The artifact that represent the milestone
     *
     * For instance a Sprint or a Release
     *
     * @var Artifact
     */
    private $artifact;

    /**
     * The planned artifacts are the content of the milestone (stuff to be done)
     *
     * Given current Milestone is a Sprint
     * And I defined a Sprint planning that associate Stories to Sprints
     * Then I will have an array of Sprint as planned artifacts.
     *
     * @var ArtifactNode
     */
    private $planned_artifacts;

    /**
     * A parent milestone is the milestone that contains the current one.
     *
     * Given current Milestone is a Sprint
     * And there is a Parent/Child association between Release and Sprint
     * And there is a Parent/Child association between Product and Release
     * Then $parent_milestones will be a Release and a Product
     *
     * @var array of Planning_Milestone
     */
    private $parent_milestones = [];

    private ?DatePeriodWithOpenDays $date_period = null;

    /**
     * The capacity of the milestone
     *
     * @var float
     */
    private $capacity = null;

     /**
     * The remaining effort of the milestone
     *
     * @var float
     */
    private $remaining_effort = null;

    public function __construct(
        Project $project,
        Planning $planning,
        Artifact $artifact,
        ?ArtifactNode $planned_artifacts = null,
    ) {
        $this->project           = $project;
        $this->planning          = $planning;
        $this->artifact          = $artifact;
        $this->planned_artifacts = $planned_artifacts;
    }

    /**
     * @return int The project identifier.
     */
    #[Override]
    public function getGroupId()
    {
        return $this->project->getID();
    }

    /**
     * @return Project
     */
    #[Override]
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return Artifact
     */
    #[Override]
    public function getArtifact()
    {
        return $this->artifact;
    }

    /**
     * @return bool
     */
    #[Override]
    public function userCanView(PFUser $user)
    {
        return $this->artifact->getTracker()->userCanView($user);
    }

    /**
     * @return int
     */
    #[Override]
    public function getTrackerId()
    {
        return $this->artifact->getTrackerId();
    }

    /**
     * @return int
     */
    #[Override]
    public function getArtifactId()
    {
        return $this->artifact->getId();
    }

    /**
     * @return string
     */
    #[Override]
    public function getArtifactTitle()
    {
        return $this->artifact->getTitle() ?? '';
    }

    /**
     * @return string
     */
    #[Override]
    public function getXRef()
    {
        return $this->artifact->getXRef();
    }

    /**
     * @return Planning
     */
    #[Override]
    public function getPlanning()
    {
        return $this->planning;
    }

    /**
     * @return int
     */
    #[Override]
    public function getPlanningId()
    {
        return $this->planning->getId();
    }

    /**
     * @return ArtifactNode
     */
    #[Override]
    public function getPlannedArtifacts()
    {
        return $this->planned_artifacts;
    }

    /**
     * All artifacts linked by either the root artifact or any of the artifacts in plannedArtifacts()
     * @return Artifact[]
     */
    #[Override]
    public function getLinkedArtifacts(PFUser $user)
    {
        $artifacts = $this->artifact->getUniqueLinkedArtifacts($user);
        $root_node = $this->getPlannedArtifacts();
        // TODO get rid of this if, in favor of an empty treenode
        if ($root_node) {
            $this->addChildrenNodes($root_node, $artifacts, $user);
        }
        return $artifacts;
    }

    private function addChildrenNodes(ArtifactNode $root_node, &$artifacts, $user)
    {
        foreach ($root_node->getChildren() as $node) {
            $artifact    = $node->getObject();
            $artifacts[] = $artifact;
            $artifacts   = array_merge($artifacts, $artifact->getUniqueLinkedArtifacts($user));
            $this->addChildrenNodes($node, $artifacts, $user);
        }
    }

    #[Override]
    public function hasAncestors()
    {
        return ! empty($this->parent_milestones);
    }

    #[Override]
    public function getAncestors()
    {
        return $this->parent_milestones;
    }

    #[Override]
    public function getParent()
    {
        $parent_milestones_values = array_values($this->parent_milestones);
        return array_shift($parent_milestones_values);
    }

    #[Override]
    public function setAncestors(array $ancestors)
    {
        $this->parent_milestones = $ancestors;
    }

    #[Override]
    public function getStartDate()
    {
        if ($this->date_period !== null) {
            return $this->date_period->getStartDate();
        }

        return null;
    }

    #[Override]
    public function getEndDate()
    {
        if (! $this->getStartDate()) {
            return null;
        }

        if ($this->getDuration() === null || $this->getDuration() <= 0) {
            return null;
        }

        if ($this->date_period !== null) {
            return (int) $this->date_period->getEndDate();
        }

        return null;
    }

    #[Override]
    public function getDaysSinceStart()
    {
        if ($this->date_period !== null) {
            return $this->date_period->getNumberOfDaysSinceStart();
        }

        return null;
    }

    #[Override]
    public function getDaysUntilEnd()
    {
        if ($this->date_period !== null) {
            return $this->date_period->getNumberOfDaysUntilEnd();
        }

        return null;
    }

    #[Override]
    public function getCapacity()
    {
        return $this->capacity;
    }

    #[Override]
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    }

    #[Override]
    public function getRemainingEffort()
    {
        return $this->remaining_effort;
    }

    #[Override]
    public function setRemainingEffort($remaining_effort)
    {
        $this->remaining_effort = $remaining_effort;
    }

    /**
     * @return bool True if nothing went wrong
     */
    public function solveInconsistencies(PFUser $user, array $artifacts_ids)
    {
        $artifact = $this->getArtifact();

        return $artifact->linkArtifacts($artifacts_ids, $user);
    }

    /**
     * Get the timestamp of the last modification of the milestone
     *
     * @return int
     */
    #[Override]
    public function getLastModifiedDate()
    {
        return $this->getArtifact()->getLastUpdateDate();
    }

    /**
     * @see Planning_Milestone::getDuration()
     * @return int|null
     */
    #[Override]
    public function getDuration()
    {
        if ($this->date_period !== null) {
            return $this->date_period->getDuration();
        }

        return null;
    }

    #[Override]
    public function milestoneCanBeSubmilestone(Planning_Milestone $potential_submilestone): bool
    {
        if ($potential_submilestone->getArtifact()->getTracker()->getParent()) {
            $parent = $potential_submilestone->getArtifact()->getTracker()->getParent();
            if ($parent === null) {
                throw new RuntimeException('Tracker does not exist');
            }
            return $parent->getId() == $this->getArtifact()->getTracker()->getId();
        }

        return false;
    }

    /**
     * @return bool
     */
    #[Override]
    public function hasBurdownField(PFUser $user)
    {
        $burndown_field = $this->getArtifact()->getABurndownField($user);

        return (bool) $burndown_field;
    }

    #[Override]
    public function setDatePeriod(DatePeriodWithOpenDays $date_period): void
    {
        $this->date_period = $date_period;
    }

    #[Override]
    public function getPromotedMilestoneId(): string
    {
        return self::PROMOTED_ITEM_PREFIX . $this->getArtifactId();
    }
}
