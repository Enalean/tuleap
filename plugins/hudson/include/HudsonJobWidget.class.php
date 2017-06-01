<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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
require_once('HudsonWidget.class.php');
require_once('common/widget/Widget.class.php');
require_once('PluginHudsonJobDao.class.php');

abstract class HudsonJobWidget extends HudsonWidget {

    var $widget_id;
    var $group_id;

    var $job;
    var $job_url;
    var $job_id;

    function isUnique() {
        return false;
    }

    function create(&$request) {
        $content_id = false;
        $vId = new Valid_Uint($this->widget_id . '_job_id');
        $vId->setErrorMessage("Can't add empty job id");
        $vId->required();
        if ($request->valid($vId)) {
            $job_id = $request->get($this->widget_id . '_job_id');
            $sql = 'INSERT INTO plugin_hudson_widget (widget_name, owner_id, owner_type, job_id) VALUES ("' . $this->id . '", '. $this->owner_id .", '". $this->owner_type ."', " . db_escape_int($job_id) ." )";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }

    function destroy($id) {
        $sql = 'DELETE FROM plugin_hudson_widget WHERE id = '. $id .' AND owner_id = '. $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
        db_query($sql);
    }

    function getInstallPreferences() {
        $purifier = Codendi_HTMLPurifier::instance();
        $prefs    = '<strong>'.$purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'monitored_job')).'</strong><br />';

        $selected_jobs_id = $this->getSelectedJobsId();
        $jobs = $this->getAvailableJobs();

        $only_one_job = (count($jobs) == 1);
	    foreach ($jobs as $job_id => $job) {
                $selected = ($only_one_job)?'checked="checked"':'';
                $prefs .= '<input type="radio" name="' . $purifier->purify($this->widget_id) . '_job_id" value="'.$purifier->purify($job_id).'" ' . $selected . '> ' . $purifier->purify($job->getName()) ;
                if (in_array($job_id, $selected_jobs_id)) {
                        $prefs .= ' <em>('. $purifier->purify($GLOBALS['Language']->getText('widget_add', 'already_used')) .')</em>';
                }
                $prefs .= '<br />';
	    }
        return $prefs;
    }

    function hasPreferences() {
        return true;
    }

    public function getPreferencesForBurningParrot($widget_id)
    {
        $this->initContent();
        $purifier = Codendi_HTMLPurifier::instance();

        $html = '<div class="tlp-form-element">
            <label class="tlp-label">
                '.$purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'monitored_job')).'
            </label>
            <select class="tlp-select"
                id="job-'. (int)$widget_id .'"
                name="' . $purifier->purify($this->widget_id) . '_job_id">';

        foreach ($this->getAvailableJobs() as $job_id => $job) {
            $selected = ($job_id == $this->job_id) ? 'selected="seleceted"' : '';

            $html .= '<option value="'.$purifier->purify($job_id).'" ' . $selected . '>
                '.$purifier->purify($job->getName()).'
                </option>';
        }
        $html .= '</select>
            </div>';

        return $html;
    }

    function getPreferences() {
        $purifier = Codendi_HTMLPurifier::instance();
        $prefs    = '<strong>'.$purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'monitored_job')).'</strong><br />';

        $jobs = $this->getAvailableJobs();

        foreach ($jobs as $job_id => $job) {
            $selected = ($job_id == $this->job_id)?'checked="checked"':'';
            $prefs .= '<input type="radio" name="' . $purifier->purify($this->widget_id) . '_job_id" value="'.$purifier->purify($job_id).'" ' . $selected . '> '.$purifier->purify($job->getName()).'<br />';
        }
        return $prefs;
    }

    function updatePreferences(&$request) {
        $request->valid(new Valid_String('cancel'));
        if (!$request->exist('cancel')) {
            $job_id = $request->get($this->widget_id . '_job_id');
            $sql = "UPDATE plugin_hudson_widget SET job_id=". $job_id ." WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". (int)$request->get('content_id');
            $res = db_query($sql);
        }
        return true;
    }

    /**
     * Returns the jobs selected for this widget
     */
    function getSelectedJobsId() {
        $sql = "SELECT * FROM plugin_hudson_widget WHERE widget_name='" . $this->widget_id . "' AND owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
        $res = db_query($sql);

        $selected_jobs_id = array();
        while ($data = db_fetch_array($res)) {
        	$selected_jobs_id[] = $data['job_id'];
        }
        return $selected_jobs_id;
    }

    protected abstract function initContent();

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
