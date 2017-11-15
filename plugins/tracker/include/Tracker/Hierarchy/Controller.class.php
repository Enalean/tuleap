<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;

require_once 'common/templating/TemplateRendererFactory.class.php';
require_once 'common/valid/ValidFactory.class.php';

class Tracker_Hierarchy_Controller {

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
     * @var Tracker_Hierarchy_Dao
     */
    private $dao;

    /**
     * @var ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;

    public function __construct(
        Codendi_Request $request,
        Tracker_Hierarchy_HierarchicalTracker $tracker,
        Tracker_Hierarchy_HierarchicalTrackerFactory $factory,
        Tracker_Hierarchy_Dao $dao,
        ArtifactLinksUsageDao $artifact_links_usage_dao
    ) {
        $this->request                  = $request;
        $this->tracker                  = $tracker;
        $this->factory                  = $factory;
        $this->dao                      = $dao;
        $this->artifact_links_usage_dao = $artifact_links_usage_dao;
        $this->renderer                 = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__) . '/../../../templates');
    }
    
    public function edit()
    {
        $presenter = new Tracker_Hierarchy_Presenter(
            $this->tracker,
            $this->factory->getPossibleChildren($this->tracker),
            $this->factory->getHierarchy($this->tracker->getUnhierarchizedTracker()),
            $this->isIsChildTypeDisabledForProject($this->tracker->getProject())
        );

        $this->render('admin-hierarchy', $presenter);
    }

    /**
     * @return bool
     */
    private function isIsChildTypeDisabledForProject(Project $project)
    {
        return $this->artifact_links_usage_dao->isProjectUsingArtifactLinkTypes($project->getID()) &&
            $this->artifact_links_usage_dao->isTypeDisabledInProject(
                $project->getID(),
                Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
            );
    }

    public function update() {
        $vChildren = new Valid_UInt('children');
        $vChildren->required();

        if ($this->isIsChildTypeDisabledForProject($this->tracker->getProject())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The tracker hierarchy cannot be defined.')
            );

            return $this->redirect(
                array(
                    'tracker' => $this->tracker->getId(),
                    'func'    => 'admin-hierarchy'
                )
            );
        }

        if ($this->request->validArray($vChildren)) {
            $this->dao->updateChildren($this->tracker->getId(), $this->request->get('children'));
        } else {
            if ($this->request->exist('children')) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_hierarchy', 'controller_bad_request'));
            } else {
                $this->dao->deleteAllChildrenWithNature($this->tracker->getId());
            }
        }
        
        $this->redirect(
            array(
                'tracker' => $this->tracker->getId(),
                'func'    => 'admin-hierarchy'
            )
        );
    }
    
    private function redirect($query_parts) {
        $redirect = http_build_query($query_parts);
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'.$redirect);
    }
    
    private function render($template_name, $presenter) {
        $this->renderer->renderToPage($template_name, $presenter);
    }

    /**
     *
     * @param array $mapping the id of tracker's children
     */
    public function updateFromXmlProjectImportProcess(array $mapping) {
        $this->dao->updateChildren($this->tracker->getId(), $mapping);
    }
}
