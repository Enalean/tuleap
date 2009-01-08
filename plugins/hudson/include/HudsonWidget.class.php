<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * abstract class hudson_Widget 
 */

require_once('common/widget/Widget.class.php');
require_once('PluginHudsonJobDao.class.php');

abstract class HudsonWidget extends Widget {
    
    function getCategory() {
        return 'ci';
    }
    
    function isUnique() {
        return false;
    }
    
    function create(&$request) {
        $content_id = false;
        $vId = new Valid_Uint('job_id');
        $vId->setErrorMessage("Can't add empty job id");
        $vId->required();
        if ($request->valid($vId)) {
            $job_id = $request->get('job_id');
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
        $prefs  = '';
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'monitored_job').'</strong><br />';
        $jobs = $this->getJobsByGroup($this->group_id);
        foreach ($jobs as $job_id => $job) {
            $prefs .= '<input type="radio" name="job_id" value="'.$job_id.'"> '.$job->getName().'<br />';
        }
        return $prefs;
    }
    
    function getPreferences() {
        $prefs  = '';
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'monitored_job').'</strong><br />';
        $jobs = $this->getJobsByGroup($this->group_id);
        foreach ($jobs as $job_id => $job) {
            $selected = ($job_id == $this->job_id)?'checked="checked"':'';
            $prefs .= '<input type="radio" name="' . $this->id . '" value="'.$job_id.'" ' . $selected . '> '.$job->getName().'<br />';
        }
        return $prefs;
    }
    
    function updatePreferences(&$request) {
        $request->valid(new Valid_String('cancel'));
        if (!$request->exist('cancel')) {
            $job_id = $request->get($this->id);
            $sql = "UPDATE plugin_hudson_widget SET job_id=". $job_id ." WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". (int)$request->get('content_id');
            $res = db_query($sql); 
        }
        return true;
    }
    
    protected function getJobsByGroup($group_id) {
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByGroupID($group_id);
        $jobs = array();
        while ($dar->valid()) {
            $row = $dar->current();
            try {
                $job = new Hudsonjob($row['job_url']);
                $jobs[$row['job_id']] = $job;
            } catch (exception $e) {
                // Do not add unvalid jobs
            }
            $dar->next();
        }
        return $jobs;
    }
    
}

?>
