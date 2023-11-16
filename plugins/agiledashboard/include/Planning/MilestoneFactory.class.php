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

use Psr\Log\LoggerInterface;
use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
use Tuleap\AgileDashboard\Milestone\Request\SiblingMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\SubMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\TopMilestoneRequest;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\AgileDashboard\Planning\NotFoundException;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

/**
 * Loads planning milestones from the persistence layer.
 */
class Planning_MilestoneFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     *
     * @var AgileDashboard_Milestone_MilestoneStatusCounter
     */
    private $status_counter;

    /**
     *
     * @var PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    /**
     * @var AgileDashboard_Milestone_MilestoneDao
     */
    private $milestone_dao;

    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var MilestoneBurndownFieldChecker
     */
    private $burndown_field_checker;
    /**
     * @var array
     */
    private $cache_all_milestone = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Instanciates a new milestone factory.
     *
     * @param PlanningFactory            $planning_factory    The factory to delegate planning retrieval.
     * @param Tracker_ArtifactFactory    $artifact_factory    The factory to delegate artifacts retrieval.
     * @param Tracker_FormElementFactory $formelement_factory The factory to delegate artifacts retrieval.
     */
    public function __construct(
        PlanningFactory $planning_factory,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $formelement_factory,
        AgileDashboard_Milestone_MilestoneStatusCounter $status_counter,
        PlanningPermissionsManager $planning_permissions_manager,
        AgileDashboard_Milestone_MilestoneDao $milestone_dao,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        LoggerInterface $logger,
        MilestoneBurndownFieldChecker $burndown_field_checker,
    ) {
        $this->planning_factory             = $planning_factory;
        $this->artifact_factory             = $artifact_factory;
        $this->formelement_factory          = $formelement_factory;
        $this->status_counter               = $status_counter;
        $this->planning_permissions_manager = $planning_permissions_manager;
        $this->milestone_dao                = $milestone_dao;
        $this->scrum_mono_milestone_checker = $scrum_mono_milestone_checker;
        $this->semantic_timeframe_builder   = $semantic_timeframe_builder;
        $this->logger                       = $logger;
        $this->burndown_field_checker       = $burndown_field_checker;
    }

    public static function build(): self
    {
        $artifact_factory     = \Tracker_ArtifactFactory::instance();
        $form_element_factory = Tracker_FormElementFactory::instance();
        $planning_factory     = \PlanningFactory::build();

        return new self(
            $planning_factory,
            $artifact_factory,
            $form_element_factory,
            new AgileDashboard_Milestone_MilestoneStatusCounter(
                new AgileDashboard_BacklogItemDao(),
                new Tracker_ArtifactDao(),
                $artifact_factory
            ),
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $planning_factory),
            SemanticTimeframeBuilder::build(),
            BackendLogger::getDefaultLogger(),
            new MilestoneBurndownFieldChecker($form_element_factory)
        );
    }

    /**
     * Create a milestone corresponding to an artifact
     *
     * @param int $artifact_id
     *
     * @return Planning_Milestone|null
     */
    public function getBareMilestoneByArtifactId(PFUser $user, $artifact_id)
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact && $artifact->userCanView($user)) {
            return $this->getBareMilestoneByArtifact($user, $artifact);
        }
        return null;
    }

    /**
     * Create a milestone corresponding to an artifact
     *
     * @param int $artifact_id
     *
     * @return Planning_Milestone|null
     */
    public function getValidatedBareMilestoneByArtifactId(PFUser $user, $artifact_id)
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if (! $artifact) {
            return null;
        }
        if (! $artifact->userCanView($user)) {
            throw new MilestonePermissionDeniedException();
        }
        return $this->getBareMilestoneByArtifact($user, $artifact);
    }

    /**
     * @return Planning_Milestone|null
     */
    public function getBareMilestoneByArtifact(PFUser $user, Artifact $artifact)
    {
        $tracker  = $artifact->getTracker();
        $planning = $this->planning_factory->getPlanningByPlanningTracker($tracker);
        if ($planning) {
            return $this->getBareMilestoneByArtifactAndPlanning($user, $artifact, $planning);
        }
        return null;
    }

    /**
     * @return Planning_Milestone
     */
    private function getBareMilestoneByArtifactAndPlanning(PFUser $user, Artifact $artifact, Planning $planning)
    {
        $milestone = new Planning_ArtifactMilestone(
            $artifact->getTracker()->getProject(),
            $planning,
            $artifact,
            $this->scrum_mono_milestone_checker
        );
        $milestone->setAncestors($this->getMilestoneAncestors($user, $milestone));
        $this->updateMilestoneContextualInfo($user, $milestone);
        return $milestone;
    }

    /**
     * A Bare Milestone is a milestone with minimal information to display (ie. without planned artifacts).
     *
     * It would deserve a dedicated object but it's a bit complex to setup today due to
     * MilestoneController::getAlreadyPlannedArtifacts()
     *
     * Only objects that should be visible for the given user are loaded.
     *
     * @param int $planning_id
     * @param int $artifact_id
     *
     * @return Planning_Milestone
     * @throws Planning_NoPlanningsException
     */
    public function getBareMilestone(PFUser $user, Project $project, $planning_id, $artifact_id)
    {
        $planning = $this->planning_factory->getPlanning($planning_id);
        if ($planning === null) {
            throw new NotFoundException($planning_id);
        }
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);

        if ($artifact && $artifact->userCanView($user)) {
            return $this->getBareMilestoneByArtifactAndPlanning($user, $artifact, $planning);
        } else {
            return new Planning_NoMilestone($project, $planning);
        }
    }

    /**
     * Build a fake milestone that catch all submilestones of root planning
     *
     * @return Planning_VirtualTopMilestone
     *
     * @throws Planning_NoPlanningsException
     */
    public function getVirtualTopMilestone(PFUser $user, Project $project)
    {
        return new Planning_VirtualTopMilestone(
            $project,
            $this->planning_factory->getVirtualTopPlanning($user, $project->getID())
        );
    }

    /**
     * Add some contextual information in the given milestone
     *
     *
     * @return Planning_Milestone
     */
    public function updateMilestoneContextualInfo(PFUser $user, Planning_Milestone $milestone)
    {
        $artifact = $milestone->getArtifact();

        $milestone->setDatePeriod($this->getMilestoneDatePeriod($artifact, $user));
        $milestone->setCapacity($this->getComputedFieldValue($user, $artifact, Planning_Milestone::CAPACITY_FIELD_NAME));
        $milestone->setRemainingEffort($this->getComputedFieldValue($user, $artifact, Planning_Milestone::REMAINING_EFFORT_FIELD_NAME));

        return $milestone;
    }

    protected function getComputedFieldValue(PFUser $user, Artifact $milestone_artifact, $field_name)
    {
        $field = $this->formelement_factory->getComputableFieldByNameForUser(
            $milestone_artifact->getTracker()->getId(),
            $field_name,
            $user
        );
        if ($field) {
            return $field->getComputedValue($user, $milestone_artifact);
        }
        return 0;
    }

    /**
     * Add planned artifacts to Planning_Milestone
     *
     * Only objects that should be visible for the given user are loaded.
     *
     *
     */
    public function updateMilestoneWithPlannedArtifacts(PFUser $user, Planning_Milestone $milestone)
    {
        $planned_artifacts = $this->getPlannedArtifacts($user, $milestone->getArtifact());
        $this->removeSubMilestones($user, $milestone->getArtifact(), $planned_artifacts);

        $milestone->setPlannedArtifacts($planned_artifacts);
    }

    /**
     * Retrieves the artifacts planned for the given milestone artifact.
     *
     * @return TreeNode
     */
    public function getPlannedArtifacts(
        PFUser $user,
        Artifact $milestone_artifact,
    ) {
        if ($milestone_artifact == null) {
            return; //it is not possible!
        }

        $parents = [];
        $node    = $this->makeNodeWithChildren($user, $milestone_artifact, $parents);

        return $node;
    }

    /**
     * Adds $parent_node children according to $artifact ones.
     *
     * @param array $parents     The list of parents to prevent infinite recursion
     */
    private function addChildrenPlannedArtifacts(
        PFUser $user,
        Artifact $artifact,
        TreeNode $parent_node,
        array $parents,
    ) {
        $linked_artifacts = $artifact->getUniqueLinkedArtifacts($user);
        if (! $linked_artifacts) {
            return;
        }
        if (in_array($artifact->getId(), $parents)) {
            return;
        }

        $parents[] = $artifact->getId();
        foreach ($linked_artifacts as $linked_artifact) {
            $node = $this->makeNodeWithChildren($user, $linked_artifact, $parents);
            $parent_node->addChild($node);
        }
    }

    private function makeNodeWithChildren($user, $artifact, $parents)
    {
        $node = new ArtifactNode($artifact);
        $this->addChildrenPlannedArtifacts($user, $artifact, $node, $parents);
        return $node;
    }

    /**
     * Retrieve the sub-milestones of the given milestone.
     *
     *
     * @return Planning_Milestone[]
     */
    public function getSubMilestones(PFUser $user, Planning_Milestone $milestone)
    {
        if ($milestone instanceof Planning_VirtualTopMilestone) {
            return $this->getTopSubMilestones($user, $milestone);
        } else {
            return $this->getRegularSubMilestones($user, $milestone);
        }
    }

    /**
     * Retrieve the sub-milestones of the given milestone.
     *
     *
     * @return Planning_Milestone[]
     */
    public function getSubMilestoneIds(PFUser $user, Planning_Milestone $milestone)
    {
        if ($milestone instanceof Planning_VirtualTopMilestone) {
            return $this->getTopSubMilestoneIds($user, $milestone);
        } else {
            return $this->getRegularSubMilestoneIds($user, $milestone);
        }
    }

    private function getRegularSubMilestones(PFUser $user, Planning_Milestone $milestone)
    {
        $milestone_artifact = $milestone->getArtifact();
        $sub_milestones     = [];

        if ($milestone_artifact) {
            $sub_milestone_artifacts = $this->milestone_dao->searchSubMilestones($milestone_artifact->getId());
            $sub_milestones          = $this->convertDarToArrayOfMilestones($user, $milestone, $sub_milestone_artifacts);
        }

        return $sub_milestones;
    }

    private function getRegularSubMilestoneIds(PFUser $user, Planning_Milestone $milestone)
    {
        $milestone_artifact = $milestone->getArtifact();
        $sub_milestones_ids = [];

        if ($milestone_artifact) {
            $sub_milestone_artifacts = $this->milestone_dao->searchSubMilestones($milestone_artifact->getId());
            foreach ($sub_milestone_artifacts as $artifact_row) {
                $sub_milestones_ids[] = $artifact_row['id'];
            }
        }

        return $sub_milestones_ids;
    }

    public function getPaginatedSubMilestones(SubMilestoneRequest $request): PaginatedMilestones
    {
        $sub_milestones = [];
        $total_size     = 0;

        $milestone          = $request->getParentMilestone();
        $milestone_artifact = $milestone->getArtifact();

        if ($milestone_artifact) {
            $sub_milestone_artifacts = $this->milestone_dao->searchPaginatedSubMilestones(
                $milestone_artifact->getId(),
                $request
            );

            $total_size     = $this->milestone_dao->foundRows();
            $sub_milestones = $this->convertDarToArrayOfMilestones(
                $request->getUser(),
                $milestone,
                $sub_milestone_artifacts
            );
        }

        return new PaginatedMilestones($sub_milestones, $total_size);
    }

    public function getPaginatedSiblingMilestones(SiblingMilestoneRequest $request): PaginatedMilestones
    {
        $milestone          = $request->getReferenceMilestone();
        $milestone_artifact = $milestone->getArtifact();

        if (! $milestone_artifact) {
            return new PaginatedMilestones([], 0);
        }

        $parent = $milestone->getParent();
        if ($parent) {
            $sibling_milestones = $this->milestone_dao->searchPaginatedSiblingMilestones(
                $milestone_artifact->getId(),
                $request
            );
        } else {
            $sibling_milestones = $this->milestone_dao->searchPaginatedSiblingTopMilestones(
                $milestone_artifact->getId(),
                $milestone_artifact->getTrackerId(),
                $request
            );
        }

        $total_size = $this->milestone_dao->foundRows();
        $siblings   = $this->convertDarToArrayOfMilestones($request->getUser(), $milestone, $sibling_milestones);
        return new PaginatedMilestones($siblings, $total_size);
    }

    /**
     * @throws Planning_NoPlanningsException
     */
    public function getPaginatedTopMilestones(TopMilestoneRequest $request): PaginatedMilestones
    {
        if ($request->shouldFilterFutureMilestones()) {
            return $this->getPaginatedTopMilestonesInTheFuture($request);
        }
        if ($request->shouldFilterCurrentMilestones()) {
            return $this->getCurrentPaginatedTopMilestones($request);
        }
        return $this->getPaginatedTopMilestonesWithStatusCriterion($request);
    }

    /**
     * @throws Planning_NoPlanningsException
     */
    private function getPaginatedTopMilestonesWithStatusCriterion(TopMilestoneRequest $request): PaginatedMilestones
    {
        $top_milestones = [];
        $total_size     = 0;

        $user                          = $request->getUser();
        $project                       = $request->getProject();
        $virtual_milestone             = $this->getVirtualTopMilestone($user, $project);
        $milestone_planning_tracker_id = $virtual_milestone->getPlanning()->getPlanningTrackerId();

        if ($milestone_planning_tracker_id) {
            if ($this->scrum_mono_milestone_checker->isMonoMilestoneEnabled($project->getID()) === true) {
                $top_milestone_artifacts = $this->milestone_dao->searchPaginatedTopMilestonesForMonoMilestoneConfiguration(
                    $milestone_planning_tracker_id,
                    $request,
                );
            } else {
                $top_milestone_artifacts = $this->milestone_dao->searchPaginatedTopMilestones(
                    $milestone_planning_tracker_id,
                    $request,
                );
            }

            $total_size     = $this->milestone_dao->foundRows();
            $top_milestones = $this->convertDarToArrayOfMilestones($user, $virtual_milestone, $top_milestone_artifacts);
        }

        return new PaginatedMilestones($top_milestones, $total_size);
    }

    private function getPaginatedTopMilestonesInTheFuture(TopMilestoneRequest $request): PaginatedMilestones
    {
        $user                     = $request->getUser();
        $paginated_top_milestones = $this->getPaginatedTopMilestonesWithStatusCriterion($request);
        $milestones               = [];

        foreach ($paginated_top_milestones->getMilestones() as $milestone) {
            if (
                $this->notFutureMilestoneHasStartDate($milestone->getArtifact(), $user)
                || $this->isClosedMilestone($milestone->getArtifact())
            ) {
                continue;
            }
            $milestones[] = $milestone;
        }

        return new PaginatedMilestones($milestones, $paginated_top_milestones->getTotalSize());
    }

    private function getCurrentPaginatedTopMilestones(TopMilestoneRequest $request): PaginatedMilestones
    {
        $user                     = $request->getUser();
        $paginated_top_milestones = $this->getPaginatedTopMilestonesWithStatusCriterion($request);
        $milestones               = [];

        foreach ($paginated_top_milestones->getMilestones() as $milestone) {
            if (
                $this->notCurrentMilestoneHasStartDate($milestone->getArtifact(), $user)
                || $this->isClosedMilestone($milestone->getArtifact())
            ) {
                continue;
            }

            $milestone->setDatePeriod($this->getMilestoneDatePeriod($milestone->getArtifact(), $user));

            $milestones[] = $milestone;
        }

        return new PaginatedMilestones($milestones, $paginated_top_milestones->getTotalSize());
    }

    /**
     * @return Planning_ArtifactMilestone[]
     */
    private function convertDARToArrayOfMilestones(PFUser $user, Planning_Milestone $milestone, LegacyDataAccessResultInterface $sub_milestone_artifacts): array
    {
        $sub_milestones = [];

        foreach ($sub_milestone_artifacts as $sub_milestone_artifact) {
            $artifact = $this->artifact_factory->getInstanceFromRow($sub_milestone_artifact);
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $planning = $this->planning_factory->getPlanningByPlanningTracker($artifact->getTracker());
            if (! $planning) {
                continue;
            }

            $sub_milestone = new Planning_ArtifactMilestone(
                $milestone->getProject(),
                $planning,
                $artifact,
                $this->scrum_mono_milestone_checker
            );
            $this->addMilestoneAncestors($user, $sub_milestone);
            $this->updateMilestoneContextualInfo($user, $sub_milestone);
            $sub_milestones[] = $sub_milestone;
        }

        return $sub_milestones;
    }

    /**
     * Return the list of top most milestones
     *
     *
     * @return Planning_ArtifactMilestone[]
     */
    private function getTopSubMilestones(PFUser $user, Planning_VirtualTopMilestone $top_milestone)
    {
        $milestones = [];

        $root_planning = $this->planning_factory->getRootPlanning($user, $top_milestone->getProject()->getID());

        foreach ($this->getTopSubMilestoneArtifacts($user, $top_milestone) as $artifact) {
            if ($artifact->getLastChangeset() && $artifact->userCanView($user)) {
                $milestone = new Planning_ArtifactMilestone(
                    $top_milestone->getProject(),
                    $root_planning,
                    $artifact,
                    $this->scrum_mono_milestone_checker
                );
                $this->addMilestoneAncestors($user, $milestone);
                $this->updateMilestoneContextualInfo($user, $milestone);
                $milestones[] = $milestone;
            }
        }

        return $milestones;
    }

    private function getTopSubMilestoneIds(PFUser $user, Planning_VirtualTopMilestone $top_milestone)
    {
        $milestone_ids = [];

        foreach ($this->getTopSubMilestoneArtifacts($user, $top_milestone) as $artifact) {
            $milestone_ids[] = $artifact->getId();
        }

        return $milestone_ids;
    }

    private function getTopSubMilestoneArtifacts(PFUser $user, Planning_VirtualTopMilestone $top_milestone)
    {
        $artifacts = [];
        if (! $top_milestone->getPlanning()) {
            return $artifacts;
        }

        $milestone_planning_tracker_id = $top_milestone->getPlanning()->getPlanningTrackerId();
        if (! $milestone_planning_tracker_id) {
            return $artifacts;
        }

        return $this->artifact_factory->getArtifactsByTrackerId($milestone_planning_tracker_id);
    }

    /**
     * Returns all open milestone without their content
     *
     *
     * @return Planning_ArtifactMilestone[]
     */
    public function getAllBareMilestones(PFUser $user, Planning $planning)
    {
        $milestones = [];
        $project    = $planning->getPlanningTracker()->getProject();
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView(
            $user,
            $planning->getPlanningTrackerId()
        );
        foreach ($artifacts as $artifact) {
            $milestones[] = new Planning_ArtifactMilestone(
                $project,
                $planning,
                $artifact,
                $this->scrum_mono_milestone_checker
            );
        }

        return $milestones;
    }

    /**
     * Loads all open milestones for the given project and planning
     *
     *
     * @return Array of Planning_Milestone
     */
    public function getAllMilestones(PFUser $user, Planning $planning)
    {
        if (! isset($this->cache_all_milestone[$planning->getId()])) {
            $this->cache_all_milestone[$planning->getId()] = $this->getAllMilestonesWithoutCaching($user, $planning);
        }
        return $this->cache_all_milestone[$planning->getId()];
    }

    /**
     * Loads all milestones of a given planning without theirs planned elements
     *
     *
     * @return Array of Planning_Milestone
     */
    public function getAllMilestonesWithoutPlannedElement(PFUser $user, Planning $planning)
    {
        $project    = $planning->getPlanningTracker()->getProject();
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView(
            $user,
            $planning->getPlanningTrackerId()
        );
        $milestones = [];

        foreach ($artifacts as $artifact) {
            if ($artifact->getLastChangeset()) {
                $milestones[] = new Planning_ArtifactMilestone(
                    $project,
                    $planning,
                    $artifact,
                    $this->scrum_mono_milestone_checker,
                    null
                );
            }
        }

        return $milestones;
    }

    private function getAllMilestonesWithoutCaching(PFUser $user, Planning $planning)
    {
        $project    = $planning->getPlanningTracker()->getProject();
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView(
            $user,
            $planning->getPlanningTrackerId()
        );
        $milestones = [];

        foreach ($artifacts as $artifact) {
            /** @todo: this test is only here if we have crappy data in the db
             * ie. an artifact creation failure that leads to an incomplete artifact.
             * this should be fixed in artifact creation (transaction & co) and after
             * DB clean, the following test can be removed.
             */
            if ($artifact->getLastChangeset()) {
                $planned_artifacts = $this->getPlannedArtifacts($user, $artifact);
                $milestones[]      = new Planning_ArtifactMilestone(
                    $project,
                    $planning,
                    $artifact,
                    $this->scrum_mono_milestone_checker,
                    $planned_artifacts
                );
            }
        }

        return $milestones;
    }

    /**
     * Create a Milestone corresponding to given artifact and loads the artifacts planned for this milestone
     *
     *
     * @return Planning_ArtifactMilestone
     */
    public function getMilestoneFromArtifactWithPlannedArtifacts(Artifact $artifact, PFUser $user)
    {
        $planned_artifacts = $this->getPlannedArtifacts($user, $artifact);
        return $this->getMilestoneFromArtifact($artifact, $planned_artifacts);
    }

    /**
     * Create a Milestone corresponding to given artifact
     *
     *
     * @return Planning_ArtifactMilestone
     */
    public function getMilestoneFromArtifact(Artifact $artifact, ?TreeNode $planned_artifacts = null)
    {
        $tracker  = $artifact->getTracker();
        $planning = $this->planning_factory->getPlanningByPlanningTracker($tracker);
        if (! $planning) {
            return null;
        }

        return new Planning_ArtifactMilestone(
            $tracker->getProject(),
            $planning,
            $artifact,
            $this->scrum_mono_milestone_checker,
            $planned_artifacts
        );
    }

    /**
     * Create Milestones corresponding to an array of artifacts
     *
     * @param Artifact[] $artifacts
     *
     * @return Planning_ArtifactMilestone[]
     */
    private function getReverseKeySortedMilestonesFromArtifacts($artifacts)
    {
        krsort($artifacts);

        $milestones = [];
        foreach ($artifacts as $artifact) {
            $milestones[] = $this->getMilestoneFromArtifact($artifact);
        }

        return $milestones;
    }

    /**
     * Returns an array with all Parent milestone of given milestone.
     *
     * The array starts with current milestone, until the "oldest" ancestor
     * 0 => Sprint, 1 => Release, 2=> Product
     *
     *
     * @return Array of Planning_Milestone
     */
    public function getMilestoneAncestors(PFUser $user, Planning_Milestone $milestone)
    {
        $parent_milestone   = [];
        $milestone_artifact = $milestone->getArtifact();
        if ($milestone_artifact) {
            $parent_artifacts = $milestone_artifact->getAllAncestors($user);
            foreach ($parent_artifacts as $artifact) {
                $parent_milestone[] = $this->getMilestoneFromArtifact($artifact);
            }
        }
        $parent_milestone = array_filter($parent_milestone);
        return $parent_milestone;
    }

    /**
     * @return Planning_Milestone
     */
    public function addMilestoneAncestors(PFUser $user, Planning_Milestone $milestone)
    {
        $ancestors = $this->getMilestoneAncestors($user, $milestone);
        $milestone->setAncestors($ancestors);

        return $milestone;
    }

    /**
     * Get the top most recent milestone (last created artifact in planning tracker)
     *
     * @param int $planning_id
     *
     * @return Planning_Milestone
     */
    public function getLastMilestoneCreated(PFUser $user, $planning_id)
    {
        $planning = $this->planning_factory->getPlanning($planning_id);
        if ($planning === null) {
            throw new NotFoundException($planning_id);
        }
        $artifacts = $this->artifact_factory->getOpenArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());
        if (count($artifacts) > 0) {
            return $this->getMilestoneFromArtifact(array_shift($artifacts));
        }
        return new Planning_NoMilestone($planning->getPlanningTracker()->getProject(), $planning);
    }

    /**
     * Returns a status array. E.g.
     *  array(
     *      Tracker_Artifact::STATUS_OPEN   => no_of_opne,
     *      Tracker_Artifact::STATUS_CLOSED => no_of_closed,
     *  )
     *
     * @return array
     */
    public function getMilestoneStatusCount(PFUser $user, Planning_Milestone $milestone)
    {
        return $this->status_counter->getStatus($user, $milestone->getArtifactId());
    }

    /**
     * @return Planning_Milestone[]
     */
    public function getAllCurrentMilestones(PFUser $user, Planning $planning)
    {
        $milestones = [];
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());

        foreach ($artifacts as $artifact) {
            if ($this->notCurrentMilestoneHasStartDate($artifact, $user) || $this->isClosedMilestone($artifact)) {
                continue;
            }

            $milestones[] = $this->getMilestoneFromArtifactWithBurndownInfo($artifact, $user);
        }

        return $milestones;
    }

    /**
     * @return Planning_Milestone[]
     */
    public function getAllFutureMilestones(PFUser $user, Planning $planning)
    {
        $milestones = [];
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());

        foreach ($artifacts as $artifact) {
            if (! $artifact->isOpen() || $this->notFutureMilestoneHasStartDate($artifact, $user)) {
                continue;
            }

            $milestones[] = $this->getMilestoneFromArtifactWithBurndownInfo($artifact, $user);
        }

        return $milestones;
    }

    /**
     * Returns the last $quantity milestones - ordered by oldest first
     *
     * @return Planning_Milestone[]
     */
    public function getPastMilestones(PFUser $user, Planning $planning, $quantity)
    {
        $milestones = [];
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());

        foreach ($artifacts as $artifact) {
            if (! $this->isMilestonePast($artifact)) {
                continue;
            }

            $end_date                                         = $this->getMilestoneEndDate($artifact, $user);
            $milestones[$end_date . '_' . $artifact->getId()] = $this->getMilestoneFromArtifactWithBurndownInfo($artifact, $user);
        }
        ksort($milestones);
        $milestones = array_values($milestones);

        $count = count($milestones);
        $start = ($quantity > $count) ? 0 : $count - $quantity;

        return array_reverse(array_slice($milestones, $start));
    }

    /**
     * @return Planning_ArtifactMilestone
     */
    private function getMilestoneFromArtifactWithBurndownInfo(Artifact $artifact, PFUser $user)
    {
        $milestone = $this->getMilestoneFromArtifact($artifact);
        $milestone->setHasUsableBurndownField($this->burndown_field_checker->hasUsableBurndownField($user, $milestone));

        return $milestone;
    }

    private function getMilestoneEndDate(Artifact $milestone_artifact, PFUser $user)
    {
        return $this->getMilestoneDatePeriod($milestone_artifact, $user)
            ->getEndDate();
    }

    private function isMilestoneCurrent(Artifact $milestone_artifact, PFUser $user)
    {
        return $milestone_artifact->isOpen() && ! $this->getMilestoneDatePeriod($milestone_artifact, $user)
            ->isTodayBeforeDatePeriod();
    }

    private function isMilestoneFuture(Artifact $milestone_artifact, PFUser $user)
    {
        return $milestone_artifact->isOpen() && $this->getMilestoneDatePeriod($milestone_artifact, $user)
            ->isTodayBeforeDatePeriod();
    }

    private function isMilestonePast(Artifact $milestone_artifact)
    {
        return $milestone_artifact->getStatus() && ! $milestone_artifact->isOpen();
    }

    /**
     * @return DatePeriodWithoutWeekEnd
     */
    private function getMilestoneDatePeriod(Artifact $milestone_artifact, PFUser $user)
    {
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($milestone_artifact->getTracker());
        return $semantic_timeframe->getTimeframeCalculator()->buildDatePeriodWithoutWeekendForChangeset(
            $milestone_artifact->getLastChangeset(),
            $user,
            $this->logger
        );
    }

    private function milestoneHasStartDate(Artifact $milestone_artifact, PFUser $user)
    {
        $date_period = $this->getMilestoneDatePeriod($milestone_artifact, $user);

        return (bool) $date_period->getStartDate() > 0;
    }

    /**
     * @return Planning_ArtifactMilestone[]
     */
    public function getAllClosedMilestones(PFUser $user, Planning $planning)
    {
        $artifacts = $this->artifact_factory->getClosedArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());

        return $this->getReverseKeySortedMilestonesFromArtifacts($artifacts);
    }

    /**
     * @return Planning_ArtifactMilestone[]
     */
    public function getAllOpenMilestones(PFUser $user, Planning $planning)
    {
        $artifacts = $this->artifact_factory->getOpenArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());

        return $this->getReverseKeySortedMilestonesFromArtifacts($artifacts);
    }

    public function userCanChangePrioritiesInMilestone(Planning_Milestone $milestone, PFUser $user)
    {
        $planning                   = $milestone->getPlanning();
        $user_can_change_priorities = $this->planning_permissions_manager->userHasPermissionOnPlanning($planning->getId(), $planning->getGroupId(), $user, PlanningPermissionsManager::PERM_PRIORITY_CHANGE);

        if (! $user_can_change_priorities && $milestone->hasAncestors()) {
            return $this->userCanChangePrioritiesInMilestone($milestone->getParent(), $user);
        }

        return $user_can_change_priorities;
    }

    /**
     * @return bool
     */
    private function isClosedMilestone(Artifact $artifact)
    {
        return ($artifact->getStatus() && ! $artifact->isOpen());
    }

    /**
     * @return bool
     */
    public function notCurrentMilestoneHasStartDate(Artifact $artifact, PFUser $user)
    {
        return (! $this->isMilestoneCurrent($artifact, $user) && $this->milestoneHasStartDate($artifact, $user));
    }

    /**
     * @return bool
     */
    public function notFutureMilestoneHasStartDate(Artifact $artifact, PFUser $user)
    {
        return (! $this->isMilestoneFuture($artifact, $user) && $this->milestoneHasStartDate($artifact, $user));
    }
}
