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

namespace Tuleap\Tracker\Hierarchy;

use Codendi_Request;
use Feedback;
use Project;
use TemplateRenderer;
use TemplateRendererFactory;
use Tracker;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_Hierarchy_HierarchicalTracker;
use Tracker_Hierarchy_HierarchicalTrackerFactory;
use Tracker_Hierarchy_Presenter;
use Tracker_Workflow_Trigger_RulesDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Valid_UInt;

class HierarchyController
{

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var Tracker_Hierarchy_HierarchicalTracker
     */
    private $tracker;

    /**
     * @var Tracker_Hierarchy_HierarchicalTrackerFactory
     */
    private $factory;

    /**
     * @var HierarchyDAO
     */
    private $dao;

    /**
     * @var ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;
    /**
     * @var Tracker_Workflow_Trigger_RulesDao
     */
    private $tracker_workflow_trigger_rules_dao;
    /**
     * @var TemplateRenderer
     */
    private $renderer;

    public function __construct(
        Codendi_Request $request,
        Tracker_Hierarchy_HierarchicalTracker $tracker,
        Tracker_Hierarchy_HierarchicalTrackerFactory $factory,
        HierarchyDAO $dao,
        ArtifactLinksUsageDao $artifact_links_usage_dao,
        Tracker_Workflow_Trigger_RulesDao $tracker_workflow_trigger_rules_dao
    ) {
        $this->request                            = $request;
        $this->tracker                            = $tracker;
        $this->factory                            = $factory;
        $this->dao                                = $dao;
        $this->artifact_links_usage_dao           = $artifact_links_usage_dao;
        $this->tracker_workflow_trigger_rules_dao = $tracker_workflow_trigger_rules_dao;
        $this->renderer                           = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates');
    }

    public function edit() : void
    {
        $this->render('admin-hierarchy', $this->buildPresenter());
    }

    public function buildPresenter(): Tracker_Hierarchy_Presenter
    {
        return new Tracker_Hierarchy_Presenter(
            $this->tracker,
            $this->factory->getPossibleChildren($this->tracker),
            $this->factory->getHierarchy($this->tracker->getUnhierarchizedTracker()),
            $this->isIsChildTypeDisabledForProject($this->tracker->getProject()),
            $this->getChildrenUsedInTriggerRules()
        );
    }

    /**
     * @return Tracker[]
     * @psalm-return array<int, Tracker> Array of tracker by tracker ID
     */
    private function getChildrenUsedInTriggerRules() : array
    {
        $rows = $this->tracker_workflow_trigger_rules_dao->searchTriggeringTrackersByTargetTrackerID($this->tracker->getId());
        if ($rows === false) {
            return [];
        }
        $children_id_used_in_triggers_rules = [];
        foreach ($rows as $row) {
            $children_id_used_in_triggers_rules[$row['tracker_id']] = true;
        }

        $children_used_in_triggers_rules = [];
        foreach ($this->tracker->getChildren() as $child) {
            if (isset($children_id_used_in_triggers_rules[$child->getId()])) {
                $children_used_in_triggers_rules[$child->getId()] = $child;
            }
        }

        return $children_used_in_triggers_rules;
    }

    private function isIsChildTypeDisabledForProject(Project $project) : bool
    {
        return $this->artifact_links_usage_dao->isProjectUsingArtifactLinkTypes($project->getID()) &&
            $this->artifact_links_usage_dao->isTypeDisabledInProject(
                $project->getID(),
                Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
            );
    }

    public function update() : void
    {
        $vChildren = new Valid_UInt('children');
        $vChildren->required();

        if ($this->isIsChildTypeDisabledForProject($this->tracker->getProject())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The tracker hierarchy cannot be defined.')
            );

            $this->redirectToAdminHierarchy();
            return;
        }

        if (! $this->request->validArray($vChildren) && $this->request->exist('children')) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_hierarchy', 'controller_bad_request'));
            $this->redirectToAdminHierarchy();
            return;
        }
        /** @var int[]|false $wanted_children */
        $wanted_children = $this->request->get('children');
        if ($wanted_children === false) {
            $wanted_children = [];
        }

        $children_used_in_trigger_rules = $this->getChildrenUsedInTriggerRules();

        $this->dao->updateChildren(
            $this->tracker->getId(),
            array_merge($wanted_children, array_keys($children_used_in_trigger_rules))
        );

        $this->redirectToAdminHierarchy();
    }

    private function redirectToAdminHierarchy() : void
    {
        $redirect = http_build_query(
            [
                'tracker' => $this->tracker->getId(),
                'func'    => 'admin-hierarchy'
            ]
        );
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?' . $redirect);
    }

    private function render($template_name, $presenter)
    {
        $this->renderer->renderToPage($template_name, $presenter);
    }

    /**
     *
     * @param array $mapping the id of tracker's children
     */
    public function updateFromXmlProjectImportProcess(array $mapping)
    {
        $this->dao->updateChildren($this->tracker->getId(), $mapping);
    }
}
