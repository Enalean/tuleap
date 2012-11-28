<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/templating/TemplateRendererFactory.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/CrossSearch/SearchContentView.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/CardFields.class.php';
require_once 'common/TreeNode/TreeNodeMapper.class.php';

class Planning_SearchContentView extends Tracker_CrossSearch_SearchContentView {

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    /**
     * @var Tracker_TreeNode_CardPresenterNode
     */
    private $tree_of_card_presenters;

    // Presenter properties
    private $planning;
    private $backlog_actions_presenter;
    public $planning_redirect_parameter = '';


    public function __construct(
        Tracker_Report $report,
        array $criteria,
        TreeNode $tree_of_artifacts,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $factory,
        PFUser $user,
        Planning_BacklogActionsPresenter $backlog_actions_presenter,
        Planning $planning,
        $planning_redirect_parameter
    ) {
        parent::__construct($report, $criteria, $tree_of_artifacts, $artifact_factory, $factory, $user);

        $this->backlog_actions_presenter   = $backlog_actions_presenter;
        $this->planning                    = $planning;
        $this->planning_redirect_parameter = $planning_redirect_parameter;
        $this->renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__) .'/../../templates');

        $card_mapper = new TreeNodeMapper(new Planning_ItemCardPresenterCallback($this->planning, new Tracker_CardFields(), $user, 'planning-draggable-toplan'));
        $this->tree_of_card_presenters = $card_mapper->map($this->tree_of_artifacts);
    }

    public function fetchResultActions() {
        return $this->renderer->renderToString('backlog-actions', $this->backlog_actions_presenter);
    }

    protected function fetchNoMatchingArtifacts() {
        //we need the empty structure to be able to remove item from the plan
        return parent::fetchNoMatchingArtifacts() . $this->fetchTable();
    }

    protected function fetchTable() {
        return $this->renderer->renderToString('backlog', $this);
    }

    public function getChildren() {
        return $this->tree_of_card_presenters->getChildren();
    }

    public function allowedChildrenTypes() {
        return $this->planning->getBacklogTracker();
    }

    public function setRenderer(TemplateRenderer $renderer) {
        $this->renderer = $renderer;
    }
}
?>
