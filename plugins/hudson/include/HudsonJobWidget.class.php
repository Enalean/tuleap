<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Dashboard\Project\ProjectDashboardController;

abstract class HudsonJobWidget extends HudsonWidget
{

    public $widget_id;
    public $group_id;

    public $job_id;

    public function isUnique()
    {
        return false;
    }

    public function create(Codendi_Request $request)
    {
        $content_id = false;
        $vId = new Valid_UInt($this->widget_id . '_job_id');
        $vId->setErrorMessage("Can't add empty job id");
        $vId->required();
        if ($request->valid($vId)) {
            $job_id = $request->get($this->widget_id . '_job_id');
            $sql = 'INSERT INTO plugin_hudson_widget (widget_name, owner_id, owner_type, job_id) VALUES ("' . $this->id . '", ' . $this->owner_id . ", '" . $this->owner_type . "', " . db_escape_int($job_id) . " )";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }

    public function destroy($id)
    {
        $sql = 'DELETE FROM plugin_hudson_widget WHERE id = ' . $id . ' AND owner_id = ' . $this->owner_id . " AND owner_type = '" . $this->owner_type . "'";
        db_query($sql);
    }

    public function getPreferences($widget_id)
    {
        $select_id = 'job-' . (int) $widget_id;

        return $this->buildPreferencesForm($select_id);
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getInstallPreferences()
    {
        $select_id = 'widget-job-id';

        return $this->buildPreferencesForm($select_id);
    }

    private function buildPreferencesForm($select_id)
    {
        $this->initContent();
        $purifier = Codendi_HTMLPurifier::instance();

        $jobs = $this->getAvailableJobs();
        if (count($jobs) > 0) {
            $html = '<div class="tlp-form-element">
                <label class="tlp-label" for="' . $select_id . '">
                    ' . $purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'monitored_job')) . '
                </label>
                <select class="tlp-select"
                    id="' . $select_id . '"
                    name="' . $purifier->purify($this->widget_id) . '_job_id">';

            foreach ($jobs as $job_id => $job) {
                $selected = ($job_id == $this->job_id) ? 'selected="seleceted"' : '';

                $html .= '<option value="' . $purifier->purify($job_id) . '" ' . $selected . '>
                ' . $purifier->purify($job->getName()) . '
                </option>';
            }
            $html .= '</select>
                </div>';
        } elseif ($this->owner_type == ProjectDashboardController::LEGACY_DASHBOARD_TYPE) {
            $html = '<div class="tlp-alert-warning">' . $GLOBALS['Language']->getText(
                'plugin_hudson',
                'widget_no_job_project',
                array($this->group_id)
            ) . '</div>';
        } else {
            $message = $this->owner_type == ProjectDashboardController::LEGACY_DASHBOARD_TYPE ?
                $GLOBALS['Language']->getText('plugin_hudson', 'widget_no_job_project', array($this->group_id)) :
                $GLOBALS['Language']->getText('plugin_hudson', 'widget_no_job_my');

            $html = '<div class="tlp-alert-warning">' . $message . '</div>';
        }

        return $html;
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $request->valid(new Valid_String('cancel'));
        if (!$request->exist('cancel')) {
            $job_id = $request->get($this->widget_id . '_job_id');
            $sql = "UPDATE plugin_hudson_widget SET job_id=" . $job_id . " WHERE owner_id = " . $this->owner_id . " AND owner_type = '" . $this->owner_type . "' AND id = " . (int) $request->get('content_id');
            $res = db_query($sql);
        }
        return true;
    }

    abstract protected function initContent();

    /**
     * @return int|null
     */
    protected function getJobIdFromWidgetConfiguration()
    {
        $sql = "SELECT *
                    FROM plugin_hudson_widget
                    WHERE widget_name = '" . db_es($this->widget_id) . "'
                      AND owner_id = " . db_ei($this->owner_id) . "
                      AND owner_type = '" . db_es($this->owner_type) . "'
                      AND id = " . db_ei($this->content_id);

        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data   = db_fetch_array($res);
            return $data['job_id'];
        }

        return null;
    }
}
