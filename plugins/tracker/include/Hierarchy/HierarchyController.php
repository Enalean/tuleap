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

declare(strict_types=1);

namespace Tuleap\Tracker\Hierarchy;

use Codendi_Request;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use TemplateRendererFactory;
use Tracker;
use Tracker_Hierarchy_HierarchicalTracker;
use Tracker_Hierarchy_HierarchicalTrackerFactory;
use Tracker_Workflow_Trigger_RulesDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Valid_UInt;

final readonly class HierarchyController
{
    final public const HIERARCHY_VIEW = 'admin-hierarchy';
    private TemplateRenderer $renderer;

    public function __construct(
        private Codendi_Request $request,
        private Tracker_Hierarchy_HierarchicalTracker $tracker,
        private Tracker_Hierarchy_HierarchicalTrackerFactory $factory,
        private HierarchyDAO $dao,
        private Tracker_Workflow_Trigger_RulesDao $tracker_workflow_trigger_rules_dao,
        private ArtifactLinksUsageDao $artifact_links_usage_dao,
        private EventDispatcherInterface $event_dispatcher,
        private \ProjectHistoryDao $project_history_dao,
        private BaseLayout $layout,
        private CSRFSynchronizerTokenInterface $csrf_token,
    ) {
        $this->renderer = TemplateRendererFactory::build()->getRenderer(__DIR__);
    }

    public function includeHeaderAssets(): void
    {
        $this->layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons(
                new IncludeAssets(
                    __DIR__ . '/../../scripts/tracker-admin/frontend-assets',
                    '/assets/trackers/tracker-admin'
                ),
                'hierarchy'
            )
        );
    }

    public function edit(): void
    {
        $this->renderer->renderToPage('admin-hierarchy', $this->buildPresenter());
    }

    public function buildPresenter(): HierarchyPresenter
    {
        return new HierarchyPresenter(
            $this->tracker,
            $this->factory->getPossibleChildren($this->tracker),
            $this->factory->getHierarchy($this->tracker->getUnhierarchizedTracker()),
            $this->getChildrenUsedInTriggerRules(),
            $this->csrf_token,
        );
    }

    /**
     * @return Tracker[]
     * @psalm-return array<int, Tracker> Array of tracker by tracker ID
     */
    private function getChildrenUsedInTriggerRules(): array
    {
        $rows = $this->tracker_workflow_trigger_rules_dao->searchTriggeringTrackersByTargetTrackerID($this->tracker->getId());
        if ($rows === false || $rows === []) {
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

    public function update(): void
    {
        $this->csrf_token->check();

        $vChildren = new Valid_UInt('children');
        $vChildren->required();

        if (! $this->request->validArray($vChildren) && $this->request->exist('children')) {
            $this->layout->addFeedback('error', dgettext('tuleap-tracker', 'Your request contains invalid data, cowardly doing nothing (children parameter)'));
            $this->redirectToAdminHierarchy();
            return;
        }
        /** @var string[]|false $request_children */
        $request_children = $this->request->get('children');
        /** @var int[] $wanted_children */
        $wanted_children = [];
        if ($request_children !== false) {
            $wanted_children = array_map('intval', $request_children);
        }

        $event = $this->event_dispatcher->dispatch(
            new TrackerHierarchyUpdateEvent(
                $this->tracker->getUnhierarchizedTracker(),
                $wanted_children,
            )
        );

        if (! $event->canHierarchyBeUpdated()) {
            $this->layout->addFeedback(
                'error',
                $event->getErrorMessage(),
            );
            $this->redirectToAdminHierarchy();
            return;
        }

        $children_used_in_trigger_rules = $this->getChildrenUsedInTriggerRules();

        $user     = $this->request->getCurrentUser();
        $children = array_merge($wanted_children, array_keys($children_used_in_trigger_rules));
        if ($this->artifact_links_usage_dao->isProjectUsingArtifactLinkTypes((int) $this->tracker->getProject()->getID())) {
            $this->dao->changeTrackerHierarchy(
                $this->tracker->getId(),
                $children
            );
        } else {
            //If project does not use the artifact link types yet, _is_child must continue
            //to be automatically set to that when an admin will enable the types everything
            //will be consistent
            $this->dao->updateChildren(
                $this->tracker->getId(),
                $children
            );
        }

        $current_hierarchy = [];
        foreach ($this->tracker->getChildren() as $child) {
            $current_hierarchy[] = $child->getId();
        }

        $children_string          = implode(',', array_values($children));
        $current_hierarchy_string = implode(',', $current_hierarchy);

        $this->project_history_dao->addHistory(
            $this->tracker->getProject(),
            $user,
            new \DateTimeImmutable(),
            HierarchyHistoryEntry::HierarchyUpdate->value,
            '',
            [
                $this->tracker->getId(),
                $children_string,
                $current_hierarchy_string,
            ]
        );
        $this->redirectToAdminHierarchy();
    }

    private function redirectToAdminHierarchy(): void
    {
        $redirect = http_build_query(
            [
                'tracker' => $this->tracker->getId(),
                'func'    => self::HIERARCHY_VIEW,
            ]
        );
        $this->layout->redirect('/plugins/tracker/?' . $redirect);
    }

    /**
     *
     * @param array $mapping the id of tracker's children
     */
    public function updateFromXmlProjectImportProcess(array $mapping): void
    {
        $this->dao->updateChildren($this->tracker->getId(), $mapping);
    }
}
