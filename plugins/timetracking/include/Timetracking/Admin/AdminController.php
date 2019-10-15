<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\Admin;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use PermissionsNormalizer;
use PermissionsNormalizerOverrideCollection;
use Project;
use ProjectHistoryDao;
use TemplateRendererFactory;
use Tracker;
use TrackerManager;
use User_ForgeUserGroupFactory;

class AdminController
{
    public const WRITE_ACCESS = 'PLUGIN_TIMETRACKING_WRITE';
    public const READ_ACCESS  = 'PLUGIN_TIMETRACKING_READ';

    /**
     * @var TrackerManager
     */
    private $tracker_manager;

    /**
     * @var TimetrackingEnabler
     */
    private $enabler;

    /**
     * @var User_ForgeUserGroupFactory
     */
    private $user_group_factory;

    /**
     * @var PermissionsNormalizer
     */
    private $permissions_normalizer;

    /**
     * @var TimetrackingUgroupSaver
     */
    private $timetracking_ugroup_saver;

    /**
     * @var TimetrackingUgroupRetriever
     */
    private $timetracking_ugroup_retriever;

    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;

    public function __construct(
        TrackerManager $tracker_manager,
        TimetrackingEnabler $enabler,
        User_ForgeUserGroupFactory $user_group_factory,
        PermissionsNormalizer $permissions_normalizer,
        TimetrackingUgroupSaver $timetracking_ugroup_saver,
        TimetrackingUgroupRetriever $timetracking_ugroup_retriever,
        ProjectHistoryDao $project_history_dao
    ) {
        $this->tracker_manager               = $tracker_manager;
        $this->enabler                       = $enabler;
        $this->user_group_factory            = $user_group_factory;
        $this->permissions_normalizer        = $permissions_normalizer;
        $this->timetracking_ugroup_saver     = $timetracking_ugroup_saver;
        $this->timetracking_ugroup_retriever = $timetracking_ugroup_retriever;
        $this->project_history_dao           = $project_history_dao;
    }

    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFSynchronizerToken(Tracker $tracker)
    {
        return new CSRFSynchronizerToken($tracker->getAdministrationUrl());
    }

    public function displayAdminForm(Tracker $tracker)
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(TIMETRACKING_TEMPLATE_DIR);
        $presenter = new AdminPresenter(
            $tracker,
            $this->getCSRFSynchronizerToken($tracker),
            $this->enabler->isTimetrackingEnabledForTracker($tracker),
            $this->getReadersUGroupPresenters($tracker),
            $this->getWritersUGroupPresenters($tracker)
        );

        $tracker->displayAdminItemHeader(
            $this->tracker_manager,
            'timetracking',
            dgettext('tuleap-timetracking', 'Time tracking')
        );

        $renderer->renderToPage(
            'tracker-admin',
            $presenter
        );

        $tracker->displayFooter($this->tracker_manager);
    }

    private function getReadersUGroupPresenters(Tracker $tracker)
    {
        $user_groups      = $this->user_group_factory->getProjectUGroupsWithAdministratorAndMembers($tracker->getProject());
        $selected_ugroups = $this->timetracking_ugroup_retriever->getReaderIdsForTracker($tracker);

        $read_ugroups = array();
        foreach ($user_groups as $ugroup) {
            $read_ugroups[] = array(
                'label'    => $ugroup->getName(),
                'value'    => $ugroup->getId(),
                'selected' => in_array($ugroup->getId(), $selected_ugroups)
            );
        }

        return $read_ugroups;
    }

    private function getWritersUGroupPresenters(Tracker $tracker)
    {
        $user_groups      = $this->user_group_factory->getProjectUGroupsWithAdministratorAndMembers($tracker->getProject());
        $selected_ugroups = $this->timetracking_ugroup_retriever->getWriterIdsForTracker($tracker);

        $write_ugroups = array();
        foreach ($user_groups as $ugroup) {
            $write_ugroups[] = array(
                'label'    => $ugroup->getName(),
                'value'    => $ugroup->getId(),
                'selected' => in_array($ugroup->getId(), $selected_ugroups)
            );
        }

        return $write_ugroups;
    }

    public function editTimetrackingAdminSettings(Tracker $tracker, Codendi_Request $request)
    {
        $csrf = $this->getCSRFSynchronizerToken($tracker);
        $csrf->check();

        if ($request->get('enable_timetracking') && ! $this->enabler->isTimetrackingEnabledForTracker($tracker)) {
            $this->enableTimetracking($tracker);
        } elseif (! $request->get('enable_timetracking') && $this->enabler->isTimetrackingEnabledForTracker($tracker)) {
            $this->disableTimetracking($tracker);
        } elseif ($this->isTimetrackingAlreadyEnabled($tracker, $request)) {
            $this->saveUgroups($tracker, $request);
        }
    }

    private function enableTimetracking(Tracker $tracker)
    {
        $this->enabler->enableTimetrackingForTracker($tracker);

        $this->project_history_dao->groupAddHistory(
            'timetracking_enabled',
            'Time tracking enabled for tracker ' . $tracker->getName(),
            $tracker->getGroupId()
        );

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-timetracking', 'Time tracking is enabled for tracker.')
        );
    }

    private function disableTimetracking(Tracker $tracker)
    {
        $this->enabler->disableTimetrackingForTracker($tracker);

        $this->project_history_dao->groupAddHistory(
            'timetracking_disabled',
            'Time tracking disabled for tracker ' . $tracker->getName(),
            $tracker->getGroupId()
        );

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-timetracking', 'Time tracking is disabled for tracker.')
        );
    }

    private function isTimetrackingAlreadyEnabled(Tracker $tracker, Codendi_Request $request)
    {
        return $request->get('enable_timetracking') && $this->enabler->isTimetrackingEnabledForTracker($tracker);
    }

    private function saveUgroups(Tracker $tracker, Codendi_Request $request)
    {
        $selected_write_ugroups = $request->get('write_ugroups');
        if ($selected_write_ugroups) {
            $this->saveWriters($tracker, $selected_write_ugroups);
        } else {
            $this->timetracking_ugroup_saver->deleteWriters($tracker);
        }

        $selected_read_ugroups = $request->get('read_ugroups');
        if ($selected_read_ugroups) {
            $this->saveReaders($tracker, $selected_read_ugroups);
        } else {
            $this->timetracking_ugroup_saver->deleteReaders($tracker);
        }

        $this->project_history_dao->groupAddHistory(
            'timetracking_permissions_updated',
            'Time tracking permissions updated for tracker ' . $tracker->getName(),
            $tracker->getGroupId()
        );

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-timetracking', 'Permissions successfully saved.')
        );
    }

    private function saveWriters(Tracker $tracker, $selected_write_ugroup)
    {
        $override_collection = new PermissionsNormalizerOverrideCollection();
        $normalized_ids = $this->permissions_normalizer->getNormalizedUGroupIds(
            $tracker->getProject(),
            $selected_write_ugroup,
            $override_collection
        );

        if ($this->timetracking_ugroup_saver->saveWriters($tracker, $normalized_ids)) {
            $override_collection->emitFeedback(self::WRITE_ACCESS);
        }
    }

    private function saveReaders(Tracker $tracker, $selected_read_ugroup)
    {
        $override_collection = new PermissionsNormalizerOverrideCollection();
        $normalized_ids = $this->permissions_normalizer->getNormalizedUGroupIds(
            $tracker->getProject(),
            $selected_read_ugroup,
            $override_collection
        );

        if ($this->timetracking_ugroup_saver->saveReaders($tracker, $normalized_ids)) {
            $override_collection->emitFeedback(self::READ_ACCESS);
        }
    }
}
