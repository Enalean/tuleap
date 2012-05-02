<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Widget.class.php');
require_once('common/survey/SurveySingleton.class.php');

/**
* Widget_MySurveys
* 
* DEVELOPER SURVEYS
* 
* This needs to be updated manually to display any given survey
* Default behavior: get first survey from group #1 
*/
class Widget_MySurveys extends Widget {
    var $content;
    var $can_be_displayed;
    
    function Widget_MySurveys() {
        $this->Widget('mysurveys');
        $no_survey = true;
        // Get id and title of the survey that will be promoted to user page. default = survey whose id=1
        if ($GLOBALS['sys_my_page_survey']) {
            $developer_survey_id = $GLOBALS['sys_my_page_survey'];
        } else {
            $developer_survey_id = "1";
        }
        
        $survey       =& SurveySingleton::instance();
        $sql          = "SELECT * from surveys WHERE survey_id=". $developer_survey_id;
        $result       = db_query($sql);
        $group_id     = db_result($result, 0, 'group_id');
        $survey_title = $survey->getSurveyTitle(db_result($result, 0, 'survey_title'));
        
        // Check that the survey is active
        $devsurvey_is_active = db_result($result, 0, 'is_active');
        
        if ($devsurvey_is_active==1) {
        
            $sql="SELECT * FROM survey_responses ".
            "WHERE survey_id='".$developer_survey_id."' AND user_id='". user_getid() ."'";
            $result = db_query($sql);
            
            if (db_numrows($result) < 1) {
                $no_survey = false;
                $this->content .= '<a href="/survey/survey.php?group_id='. $group_id .'&survey_id='. $developer_survey_id .'">'. $survey_title .'</a>';
            }             
        }
        if ($no_survey) {
            $this->content .= $GLOBALS['Language']->getText('my_index', 'no_survey');
        }
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_survey');
    }
    function getContent() {
        return $this->content;
    }
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_my_surveys','description');
    }
}

?>