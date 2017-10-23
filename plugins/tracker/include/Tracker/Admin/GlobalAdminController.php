<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

use CSRFSynchronizerToken;
use EventManager;
use Feedback;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_Hierarchy_Dao;
use Tracker_IDisplayTrackerLayout;
use Tuleap\Tracker\Events\ArtifactLinkTypeCanBeUnused;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;

class GlobalAdminController
{

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    /**
     * @var ArtifactLinksUsageDao
     */
    private $dao;

    /**
     * @var ArtifactLinksUsageUpdater
     */
    private $updater;

    /**
     * @var NaturePresenterFactory
     */
    private $types_presenter_factory;

    /**
     * @var Tracker_Hierarchy_Dao
     */
    private $hierarchy_dao;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        ArtifactLinksUsageDao $dao,
        ArtifactLinksUsageUpdater $updater,
        NaturePresenterFactory $types_presenter_factory,
        Tracker_Hierarchy_Dao $hierarchy_dao,
        CSRFSynchronizerToken $global_admin_csrf,
        EventManager $event_manager
    ) {
        $this->dao                     = $dao;
        $this->updater                 = $updater;
        $this->csrf                    = $global_admin_csrf;
        $this->types_presenter_factory = $types_presenter_factory;
        $this->hierarchy_dao           = $hierarchy_dao;
        $this->event_manager           = $event_manager;
    }

    public function displayGlobalAdministration(Project $project, Tracker_IDisplayTrackerLayout $layout)
    {
        $toolbar     = $this->getToolbar($project);
        $params      = array();
        $breadcrumbs = $this->getAdditionalBreadcrumbs($project);

        $layout->displayHeader(
            $project,
            $GLOBALS['Language']->getText('plugin_tracker', 'trackers'),
            $breadcrumbs,
            $toolbar,
            $params
        );

        $renderer  = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
        $presenter = new GlobalAdminPresenter(
            $project,
            $this->csrf,
            $this->dao->isProjectUsingArtifactLinkTypes($project->getID()),
            $this->buildFormattedTypes($project)
        );

        $renderer->renderToPage(
            'global-admin',
            $presenter
        );

        $layout->displayFooter($project);
    }

    public function updateGlobalAdministration(Project $project)
    {
        $this->csrf->check();
        $this->updater->update($project);
    }

    public function updateArtifactLinkUsage(Project $project, $type_shortname)
    {
        $type_presenter = $this->types_presenter_factory->getFromShortname($type_shortname);

        if (! $type_presenter) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The artifact link type does not exist')
            );

            return;
        }

        if ($this->dao->isTypeDisabledInProject($project->getID(), $type_shortname)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                sprintf(
                    dgettext('tuleap-tracker', 'The artifact link type "%s" is now enabled'),
                    $type_shortname
                )
            );

            return $this->dao->enableTypeInProject($project->getID(), $type_shortname);
        }

        if ($this->artifactLinkTypeCanBeUnused($project, $type_presenter)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                sprintf(
                    dgettext('tuleap-tracker', 'The artifact link type "%s" is now disabled'),
                    $type_shortname
                )
            );

            return $this->dao->disableTypeInProject($project->getID(), $type_shortname);
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext('tuleap-tracker', 'The artifact link type "%s" cannot be disabled'),
                    $type_shortname
                )
            );
        }
    }

    /**
     * @return string
     */
    public function getTrackerGlobalAdministrationURL(Project $project)
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(array(
                'func'     => 'global-admin',
                'group_id' => $project->getID()
            ));
    }

    /**
     * @return array
     */
    public function getToolbar(Project $project)
    {
        return array(
            array(
                'title' => "Administration",
                'url'   => $this->getTrackerGlobalAdministrationURL($project)
            )
        );
    }

    /**
     * @return array
     */
    private function getAdditionalBreadcrumbs(Project $project)
    {
        return $this->getToolbar($project);
    }

    /**
     * @return array
     */
    private function buildFormattedTypes(Project $project)
    {
        $formatted_types = array();
        foreach ($this->types_presenter_factory->getAllTypesEditableInProject($project) as $type) {
            $formatted_type = array(
                'shortname'     => $type->shortname,
                'forward_label' => $type->forward_label,
                'reverse_label' => $type->reverse_label,
                'is_used'       => ! $this->isTypeDisabledInProject($project, $type),
                'can_be_unused' => $this->artifactLinkTypeCanBeUnused($project, $type)
            );

            $formatted_types[] = $formatted_type;
        }

        return $formatted_types;
    }

    /**
     * @return bool
     */
    private function isTypeDisabledInProject(Project $project, NaturePresenter $type)
    {
        return $this->dao->isTypeDisabledInProject($project->getID(), $type->shortname);
    }

    /**
     * @return bool
     */
    private function artifactLinkTypeCanBeUnused(Project $project, NaturePresenter $type)
    {
        $event = new ArtifactLinkTypeCanBeUnused($project, $type);
        $this->event_manager->processEvent($event);

        if ($event->doesPluginCheckedTheType()) {
            return $event->canTypeBeUnused();
        }

        if (! $type->is_system) {
            return true;
        }

        if ($type->shortname === Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD) {
            return ! $this->hierarchy_dao->isAHierarchySetInProject($project->getID());
        }

        return false;
    }
}
