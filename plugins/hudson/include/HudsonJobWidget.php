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
        $vId        = new Valid_UInt($this->widget_id . '_job_id');
        $vId->setErrorMessage("Can't add empty job id");
        $vId->required();
        if ($request->valid($vId)) {
            $job_id     = $request->get($this->widget_id . '_job_id');
            $db         = \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB();
            $content_id = (int) $db->insertReturnId(
                'plugin_hudson_widget',
                [
                    'widget_name' => $this->id,
                    'owner_id'    => $this->owner_id,
                    'owner_type'  => $this->owner_type,
                    'job_id'      => $job_id,
                ]
            );
        }
        return $content_id;
    }

    public function destroy($id)
    {
        $db = \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run(
            'DELETE FROM plugin_hudson_widget WHERE id = ? AND owner_id = ? AND owner_type = ?',
            $id,
            $this->owner_id,
            $this->owner_type,
        );
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $select_id = 'job-' . $widget_id;

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
                    ' . $purifier->purify(dgettext('tuleap-hudson', 'Monitored job')) . '
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
            $html = '<div class="tlp-alert-warning">' . sprintf(dgettext('tuleap-hudson', 'No job found. Please <a href="/plugins/hudson/?group_id=%1$s">add a job</a> before adding any Jenkins widget.'), $this->group_id) . '</div>';
        } else {
            $message = $this->owner_type == ProjectDashboardController::LEGACY_DASHBOARD_TYPE ?
                sprintf(dgettext('tuleap-hudson', 'No job found. Please <a href="/plugins/hudson/?group_id=%1$s">add a job</a> before adding any Jenkins widget.'), $this->group_id) :
                dgettext('tuleap-hudson', 'No job found. Please add a job to any of your project before.');

            $html = '<div class="tlp-alert-warning">' . $message . '</div>';
        }

        return $html;
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $request->valid(new Valid_String('cancel'));
        if (! $request->exist('cancel')) {
            $job_id = $request->get($this->widget_id . '_job_id');
            $db     = \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB();
            $db->run(
                'UPDATE plugin_hudson_widget SET job_id=? WHERE owner_id = ? AND owner_type = ? AND id = ?',
                $job_id,
                $this->owner_id,
                $this->owner_type,
                $this->content_id,
            );
        }
        return true;
    }

    abstract protected function initContent();

    /**
     * @return int|null
     */
    protected function getJobIdFromWidgetConfiguration()
    {
        $db     = \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB();
        $job_id = $db->cell(
            'SELECT job_id FROM plugin_hudson_widget WHERE widget_name = ? AND owner_id = ? AND owner_type = ? AND id = ?',
            $this->widget_id,
            $this->owner_id,
            $this->owner_type,
            $this->content_id,
        );

        if ($job_id === false) {
            return null;
        }

        return $job_id;
    }
}
