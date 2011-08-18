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

require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_ArtifactLink.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_ArtifactLinkInfo.class.php');
require_once('dao/Tracker_FormElement_Field_Value_ArtifactLinkDao.class.php');
require_once(dirname(__FILE__).'/../Report/dao/Tracker_Report_Criteria_ArtifactLink_ValueDao.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_ArtifactFactory.class.php');
require_once(dirname(__FILE__).'/../TrackerFactory.class.php');
require_once(dirname(__FILE__).'/../Tracker_Valid_Rule.class.php');

class Tracker_FormElement_Field_ArtifactLink extends Tracker_FormElement_Field {
    
    /**
     * Display the html form in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= '<input type="text" 
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) .'" autocomplete="off" />';
        $html .= '<br />';
        $html .= '<a href="#">bug #123</a><br />';
        $html .= '<a href="#">bug #321</a><br />';
        $html .= '<a href="#">story #10234</a>';
        return $html;
    }
    
    /**
     * Display the field value as a criteria
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     */
    public function fetchCriteriaValue($criteria) {
        $html = '<input type="text" name="criteria['. $this->id .']" id="tracker_report_criteria_'. $this->id .'" value="';
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            $hp = Codendi_HTMLPurifier::instance();
            $html .= $hp->purify($criteria_value, CODENDI_PURIFIER_CONVERT_HTML);
        }
        $html .= '" />';
        return $html;
    }
    
    /**
     * Display the field as a Changeset value.
     * Used in report table
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $arr = array();
        $values = $this->getChangesetValues($changeset_id);
        foreach ($values as $artifact_link_info) {
            $arr[] = $artifact_link_info->getUrl();
        }
        $html = implode(', ', $arr);
        return $html;
    }
    
    /**
     * Display the field as a Changeset value.
     * Used in CSV data export.
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        $arr = array();
        $values = $this->getChangesetValues($changeset_id);
        foreach ($values as $artifact_link_info) {
            $arr[] = $artifact_link_info->getArtifactId();
        }
        $html = implode(',', $arr);
        return $html;
    }
    
    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value) {
        $artifact_id_array = $value->getArtifactIds();
        return implode(", ", $artifact_id_array);
    }
    
    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getSoapAvailableValues() {
        return null;
    }
    
    /**
     * Get the field data for artifact submission
     *
     * @param string the soap field value
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision
     */
    public function getFieldData($soap_value) {
        return array('new_values' => $soap_value);
    }
    
    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve 
     * the last changeset of all artifacts.
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     */
    public function getCriteriaFrom($criteria) {
        //Only filter query if field is used
        if($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a = 'A_'. $this->id;
                $b = 'B_'. $this->id;
                return " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = $this->id )
                         INNER JOIN tracker_changeset_value_artifactlink AS $b ON (
                            $b.changeset_value_id = $a.id
                            AND ". $this->buildMatchExpression("$b.artifact_id", $criteria_value) ."
                         ) ";
            }
        }
        return '';
    }
    protected $pattern = '[+\-]*[0-9]+';
    protected function cast($value) {
        return (int)$value;
    }
    protected function buildMatchExpression($field_name, $criteria_value) {
        $expr = '';
        $matches = array();
        if (preg_match('/\/(.*)\//', $criteria_value, $matches)) {
            
            // If it is sourrounded by /.../ then assume a regexp
            $expr = $field_name." RLIKE ".$this->getCriteriaDao()->da->quoteSmart($matches[1]);
        }
        if (!$expr) {
            $matches = array();
            if (preg_match("/^(<|>|>=|<=)\s*($this->pattern)\$/", $criteria_value, $matches)) {
                // It's < or >,  = and a number then use as is
                $matches[2] = (string)($this->cast($matches[2]));
                $expr = $field_name.' '.$matches[1].' '.$matches[2];
                
            } else if (preg_match("/^($this->pattern)\$/", $criteria_value, $matches)) {
                // It's a number so use  equality
                $matches[1] = $this->cast($matches[1]);
                $expr = $field_name.' = '.$matches[1];
                
            } else if (preg_match("/^($this->pattern)\s*-\s*($this->pattern)\$/", $criteria_value, $matches)) {
                // it's a range number1-number2
                $matches[1] = (string)($this->cast($matches[1]));
                $matches[2] = (string)($this->cast($matches[2]));
                $expr = $field_name.' >= '.$matches[1].' AND '.$field_name.' <= '. $matches[2];
                
            } else {
                // Invalid syntax - no condition
                $expr = '1';
            }
        }
        return $expr;
    }
    
    /**
     * Get the "where" statement to allow search with this field
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     */
    public function getCriteriaWhere($criteria) {
        return '';
    }
    
    public function getQuerySelect() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return "$R2.artifact_id AS `". $this->name . "`";
    }
    
    public function getQueryFrom() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        
        return "LEFT JOIN ( tracker_changeset_value AS $R1 
                    INNER JOIN tracker_changeset_value_artifactlink AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = ". $this->id ." )";
    }
    
    /**
     * Return the dao of the criteria value used with this field.
     * @return DataAccessObject
     */
    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_ArtifactLink_ValueDao();
    }
    
    /**
     * Fetch the html widget for the field
     *
     * @param string $name                   The name, if any
     * @param array  $artifact_links         The current artifact links
     * @param string $prefill_new_values     Prefill new values field (what the user has submitted, if any)
     $ @param array  $prefill_removed_values Pre-remove values (what the user has submitted, if any)
     * @param bool   $read_only              True if the user can't add or remove links
     *
     * @return string html
     */
    protected function fetchHtmlWidget($name, $artifact_links, $prefill_new_values, $prefill_removed_values, $read_only, $from_aid = null) {
        $html = '';
        $html_name_new = '';
        $html_name_del = '';
        if ($name) {
            $html_name_new = 'name="'. $name .'[new_values]"';
            $html_name_del = 'name="'. $name .'[removed_values]';
        }
        $hp = Codendi_HTMLPurifier::instance();
        if (!$read_only) {
            $html .= '<div><input type="text" 
                             '. $html_name_new .'
                             class="tracker-form-element-artifactlink-new"
                             size="40"
                             value="'.  $hp->purify($prefill_new_values, CODENDI_PURIFIER_CONVERT_HTML)  .'" 
                             title="' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_help') . '" />';
            $html .= '</div>';
        }
        $html .= '<div class="tracker-form-element-artifactlink-list">';
        if ($artifact_links) {
            $ids = array();
            // build an array of artifact_id / last_changeset_id for fetch renderer method
            foreach ($artifact_links as $artifact_link) {
                if (!isset($ids[$artifact_link->getTrackerId()])) {
                    $ids[$artifact_link->getTrackerId()] = array(
                        'id'                => '',
                        'last_changeset_id' => '',
                    );
                }
                if ($artifact_link->userCanView()) {
                    $ids[$artifact_link->getTrackerId()]['id'] .= $artifact_link->getArtifactId() .',';
                    $ids[$artifact_link->getTrackerId()]['last_changeset_id'] .= $artifact_link->getLastChangesetId() .',';
                }
            }
            
            $projects = array();
            $this_project_id = $this->getTracker()->getProject()->getGroupId();
            foreach ($ids as $tracker_id => $matching_ids) {
                //remove last coma
                $matching_ids['id'] = substr($matching_ids['id'], 0, -1);
                $matching_ids['last_changeset_id'] = substr($matching_ids['last_changeset_id'], 0, -1);
                
                $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
                $project = $tracker->getProject();
                if ($tracker->userCanView()) {
                    $trf = Tracker_ReportFactory::instance();
                    $report = $trf->getDefaultReportsByTrackerId($tracker->getId());
                    if ($report) {
                        $renderers = $report->getRenderers();
                        $renderer_table_found = false;
                        // looking for the first table renderer
                        foreach ($renderers as $renderer) {
                            if ($renderer->getType() === Tracker_Report_Renderer::TABLE) {
                                $projects[$project->getGroupId()][$tracker_id] = array(
                                    'project'      => $project,
                                    'tracker'      => $tracker,
                                    'report'       => $report,
                                    'renderer'     => $renderer,
                                    'matching_ids' => $matching_ids,
                                );
                                $renderer_table_found = true;
                                break;
                            }
                        }
                        if ( ! $renderer_table_found) {
                            $html .= $GLOBALS['Language']->getText('plugin_tracker', 'no_reports_available');
                        }
                    } else {
                        $html .= $GLOBALS['Language']->getText('plugin_tracker', 'no_reports_available');
                    }
                }
            }
            
            foreach ($projects as $trackers) {
                foreach ($trackers as $t) {
                    extract($t);
                    
                    $html .= '<div class="tracker-form-element-artifactlink-trackerpanel">';
                    
                    $project_name = '';
                    if ($project->getGroupId() != $this_project_id) {
                        $project_name = ' (<abbr title="'. $hp->purify($project->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML) .'">';
                        $project_name .= $hp->purify($project->getUnixName(), CODENDI_PURIFIER_CONVERT_HTML);
                        $project_name .= '</abbr>)';
                    }
                    $html .= '<h2 class="tracker-form-element-artifactlink-tracker_'. $tracker->getId() .'">';
                    $html .= $hp->purify($tracker->getName(), CODENDI_PURIFIER_CONVERT_HTML) . $project_name;
                    $html .= '</h2>';
                    if ($from_aid == null) {
                        $html .= $renderer->fetchAsArtifactLink($matching_ids, $this->getId(), $read_only, $prefill_removed_values, false);
                    } else {
                        $html .= $renderer->fetchAsArtifactLink($matching_ids, $this->getId(), $read_only, $prefill_removed_values, false, $from_aid);
                    }
                    $html .= '</div>';
                }
            }
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Process the request
     * 
     * @param TrackerManager  $tracker_manager The tracker manager
     * @param Codendi_Request $request         The data coming from the user
     * @param User            $current_user    The user who mades the request
     *
     * @return void
     */
    public function process(TrackerManager $tracker_manager, $request, $current_user) {
        switch ($request->get('func')) {
            case 'fetch-artifacts':
                $read_only              = false;
                $prefill_removed_values = array();
                $only_rows              = true;
                
                $this_project_id = $this->getTracker()->getProject()->getGroupId();
                $hp = Codendi_HTMLPurifier::instance();
                
                $u = UserManager::instance()->getCurrentUser();
                $group_id = $this->getTracker()->getGroupId();
                $ugroups = $u->getUgroups($group_id, array());
                $ugroups = implode(',', $ugroups);
                
                $ids     = $request->get('ids'); //2, 14, 15
                $tracker = array();
                $result  = array();
                //We must retrieve the last changeset ids of each artifact id.
                $dao = new Tracker_ArtifactDao();
                foreach($dao->searchLastChangesetIds($ids, $ugroups) as $matching_ids) {
                    $tracker_id = $matching_ids['tracker_id'];
                    $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
                    $project = $tracker->getProject();
                    if ($tracker->userCanView()) {
                        $trf = Tracker_ReportFactory::instance();
                        $report = $trf->getDefaultReportsByTrackerId($tracker->getId());
                        if ($report) {
                            $renderers = $report->getRenderers();
                            // looking for the first table renderer
                            foreach ($renderers as $renderer) {
                                if ($renderer->getType() === Tracker_Report_Renderer::TABLE) {
                                    $key = $this->id .'_'. $report->id .'_'. $renderer->getId();
                                    $result[$key] = $renderer->fetchAsArtifactLink($matching_ids, $this->getId(), $read_only, $prefill_removed_values, $only_rows);
                                    $head = '<div>';
                                    
                                    $project_name = '';
                                    if ($project->getGroupId() != $this_project_id) {
                                        $project_name = ' (<abbr title="'. $hp->purify($project->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML) .'">';
                                        $project_name .= $hp->purify($project->getUnixName(), CODENDI_PURIFIER_CONVERT_HTML);
                                        $project_name .= '</abbr>)';
                                    }
                                    $head .= '<h2 class="tracker-form-element-artifactlink-tracker_'. $tracker->getId() .'">';
                                    $head .= $hp->purify($tracker->getName(), CODENDI_PURIFIER_CONVERT_HTML) . $project_name;
                                    $head .= '</h2>';
                                    //if ($artifact) {
                                    //    $title = $hp->purify('link a '. $tracker->getItemName(), CODENDI_PURIFIER_CONVERT_HTML);
                                    //    $head .= '<a href="'.TRACKER_BASE_URL.'/?tracker='.$tracker_id.'&func=new-artifact-link&id='.$artifact->getId().'" class="tracker-form-element-artifactlink-link-new-artifact">'. 'create a new '.$hp->purify($tracker->getItemName(), CODENDI_PURIFIER_CONVERT_HTML)  .'</a>';
                                    //}
                                    $result[$key]['head'] = $head . $result[$key]['head'];
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($result) {
                    $head = 'head:{';
                    $rows = 'rows:{';
                    foreach($result as $key => $value) {
                        $head .= "'". $key ."':'". addslashes($value["head"]) ."',";
                        $rows .= "'". $key ."':'". addslashes($value["rows"]) ."',";
                    }
                    $head = substr($head, 0, -1) .'}';
                    $rows = substr($rows, 0, -1) .'}';
                    echo '{'. "$head,$rows" .'}';
                }
                exit();
                break;
            case 'fetch-aggregates':
                $read_only              = false;
                $prefill_removed_values = array();
                $only_rows              = true;
                $only_one_column        = false;
                $extracolumn            = Tracker_Report_Renderer_Table::EXTRACOLUMN_UNLINK;
                $read_only              = true;
                $use_data_from_db       = false;
                
                $this_project_id = $this->getTracker()->getProject()->getGroupId();
                $hp = Codendi_HTMLPurifier::instance();

                $u = UserManager::instance()->getCurrentUser();
                $group_id = $this->getTracker()->getGroupId();
                $ugroups = $u->getUgroups($group_id, array());
                $ugroups = implode(',', $ugroups);

                $ids = $request->get('ids'); //2, 14, 15
                $tracker = array();
                $json = "{'tabs':[";
                $dao = new Tracker_ArtifactDao();
                foreach ($dao->searchLastChangesetIds($ids, $ugroups) as $matching_ids) {
                    $tracker_id = $matching_ids['tracker_id'];
                    $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
                    $project = $tracker->getProject();
                    if ($tracker->userCanView()) {
                        $trf = Tracker_ReportFactory::instance();
                        $report = $trf->getDefaultReportsByTrackerId($tracker->getId());
                        if ($report) {
                            $renderers = $report->getRenderers();
                            // looking for the first table renderer
                            foreach ($renderers as $renderer) {
                                if ($renderer->getType() === Tracker_Report_Renderer::TABLE) {
                                    $key = $this->id . '_' . $report->id . '_' . $renderer->getId();
                                    $columns          = $renderer->getTableColumns($only_one_column, $use_data_from_db);
                                    $extracted_fields = $renderer->extractFieldsFromColumns($columns);
                                    $json .= "{'key':'$key',";
                                    $json .= "'src':'".$renderer->fetchAggregates($matching_ids, $extracolumn, $only_one_column,$columns, $extracted_fields, $use_data_from_db, $read_only);
                                    $json .= "'},";
                                    break;
                                }
                            }
                        }
                    }
                }
                $json .= "]}";
                echo $json;
                exit();
                break;
            default:
                parent::process($tracker_manager, $request, $current_user);
                break;
        }
    }

    /**
     * Fetch the html widget for the field
     *
     * @param string $name                   The name, if any
     * @param array  $artifact_links         The current artifact links
     * @param string $prefill_new_values     Prefill new values field (what the user has submitted, if any)     
     * @param bool   $read_only              True if the user can't add or remove links
     *
     * @return string html
     */
    protected function fetchHtmlWidgetMasschange($name, $artifact_links, $prefill_new_values, $read_only) {
        $html = '';
        $html_name_new = '';
        if ($name) {
            $html_name_new = 'name="'. $name .'[new_values]"';            
        }
        $hp = Codendi_HTMLPurifier::instance();
        if (!$read_only) {
            $html .= '<input type="text"
                             '. $html_name_new .'
                             value="'.  $hp->purify($prefill_new_values, CODENDI_PURIFIER_CONVERT_HTML)  .'"
                             title="' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_help') . '" />';
            $html .= '<br />';
        }
        if ($artifact_links) {
            $html .= '<ul class="tracker-form-element-artifactlink-list">';
            foreach ($artifact_links as $artifact_link_info) {
                $html .= '<li>';
                $html .= $artifact_link_info->getUrl();                                
                $html .= '</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        $artifact_links = array();
        if ($value != null) {
            $artifact_links = $value->getValue();
        }
        
        if (is_array($submitted_values[0])) {
            $submitted_value = $submitted_values[0][$this->getId()];
        }
        
        $prefill_new_values = '';
        if (isset($submitted_value['new_values'])) {
            $prefill_new_values = $submitted_value['new_values'];
        }
        
        $prefill_removed_values = array();
        if (isset($submitted_value['removed_values'])) {
            $prefill_removed_values = $submitted_value['removed_values'];
        }
        
        $read_only = false;
        $name      = 'artifact['. $this->id .']';
        $from_aid      = $artifact->getId();
        
        return $this->fetchHtmlWidget($name, $artifact_links, $prefill_new_values, $prefill_removed_values, $read_only, $from_aid);
    }
    
    
    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $artifact_links = array();
        if ($value != null) {
            $artifact_links = $value->getValue();
        }
        $read_only              = true;
        $name                   = '';
        $prefill_new_values     = '';
        $prefill_removed_values = array();
        return $this->fetchHtmlWidget($name, $artifact_links, $prefill_new_values, $prefill_removed_values, $read_only);
    }
    
    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values = array()) {
        $html = '';
        $prefill_new_values = '';
        if (isset($submitted_values[$this->getId()]['new_values'])) {
            $prefill_new_values = $submitted_values[$this->getId()]['new_values'];
        } else if ($this->hasDefaultValue()) {
            $prefill_new_values = $this->getDefaultValue();
        }
        
        $read_only              = false;
        $name                   = 'artifact['. $this->id .']';
        $prefill_removed_values = array();
        $artifact_links         = array();
        
        return $this->fetchHtmlWidget($name, $artifact_links, $prefill_new_values, $prefill_removed_values, $read_only);
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        $html = '';         
        $prefill_new_values     = $GLOBALS['Language']->getText('global','unchanged');
        $read_only              = false;
        $name                   = 'artifact['. $this->id .']';        
        $artifact_links         = array();
        
        return $this->fetchHtmlWidgetMasschange($name, $artifact_links, $prefill_new_values, $read_only);
    }


    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of the field
     *
     * @return string
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        if ($value != null) {
            $html = '<ul>';
            $artifact_links = $value->getValue();
            foreach($artifact_links as $artifact_link_info) {
                $html .= '<li>' . $artifact_link_info->getLabel() . '</li>';
            }
            $html = '</ul>';
        }
        return $html;
    }

    protected function getValueDao() {
        return new Tracker_FormElement_Field_Value_ArtifactLinkDao();
    }   

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        if ( empty($value) ) {
            return '';
        }
        $output = '';
        switch($format) {
            case 'html':
                $artifactlink_infos = $value->getValue();
                $output .= '<ul>';
                foreach ($artifactlink_infos as $artifactlink_info) {
                    $output .= '<li>' . $artifactlink_info->getUrl() . '</li>';
                }
                $output .= '<ul>';
                break;
            default:
                $output = PHP_EOL;
                $artifactlink_infos = $value->getValue();
                foreach ($artifactlink_infos as $artifactlink_info) {
                    $output .= $artifactlink_info->getLabel();
                    $output .= PHP_EOL;
                }
                break;
        }
        return $output;
    }
    
    /**
     * Fetch the value to display changes in followups
     *
     * @param Tracker_ $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     *
     * @return string
     */
    public function fetchFollowUp($artifact, $from, $to) {
        // never used...
    }
    
    /**
     * Fetch the value in a specific changeset
     *
     * @param Tracker_Artifact_Changeset $changeset
     *
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        // never used...
    }
    
    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param boolean                    $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed) {
        $changeset_value = null;
        $artifact_links = array();
        $rows = $this->getValueDao()->searchById($value_id, $this->id);
        while ($row = $rows->getRow()) {
            $artifact_links[$row['artifact_id']] = new Tracker_ArtifactLinkInfo($row['artifact_id'], $row['keyword'], $row['group_id'], $row['tracker_id'], $row['last_changeset_id']);
        }
        return new Tracker_Artifact_ChangesetValue_ArtifactLink($value_id, $this, $has_changed, $artifact_links);
    }
    
    /**
     * @return array
     */
    protected $artifact_links_by_changeset = array();
    
    protected function getChangesetValues($changeset_id) {
        if (!isset($this->artifact_links_by_changeset[$changeset_id])) {
            $this->artifact_links_by_changeset[$changeset_id] = array();
            
            $da = CodendiDataAccess::instance();
            $field_id     = $da->escapeInt($this->id);
            $changeset_id = $da->escapeInt($changeset_id);
            $sql = "SELECT cv.changeset_id, cv.has_changed, val.*, a.tracker_id, a.last_changeset_id 
                    FROM tracker_changeset_value_artifactlink AS val
                         INNER JOIN tracker_artifact AS a ON(a.id = val.artifact_id)
                         INNER JOIN tracker_changeset_value AS cv
                         ON ( val.changeset_value_id = cv.id
                          AND cv.field_id = $field_id
                          AND cv.changeset_id = $changeset_id
                         )
                    ORDER BY val.artifact_id";
            $dao = new DataAccessObject();
            foreach ($dao->retrieve($sql) as $row) {
                $this->artifact_links_by_changeset[$row['changeset_id']][] = new Tracker_ArtifactLinkInfo(
                    $row['artifact_id'], 
                    $row['keyword'],
                    $row['group_id'],
                    $row['tracker_id'],
                    $row['last_changeset_id']
                );
            }
        }
        return $this->artifact_links_by_changeset[$changeset_id];
    }
    
    /**
     * Check if there are changes between old and new value for this field
     *
     * @param Tracker_Artifact_ChangesetValue $old_value The data stored in the db
     * @param array                           $new_value array of artifact ids
     *
     * @return bool true if there are differences
     */
    public function hasChanges(Tracker_Artifact_ChangesetValue $old_value, $new_value) {
        return $old_value->hasChanges($new_value);
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifact_link_label');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifact_link_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/artifact-chain.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/artifact-chain--plus.png');
    }
    
    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Tracker_Artifact $artifact The artifact 
     * @param array            $value    data coming from the request. 
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Tracker_Artifact $artifact, $value) {
        if ( (! is_array($value) || empty($value['new_values'])) && $this->isRequired()) {
            $ids = $artifact->getLastChangeset()->getValue($this)->getArtifactIds();
            if ( ! empty($ids)) {
                // Field is required but there are values, so field is valid
                $this->has_errors = false;
            } else {
                $this->has_errors = true;
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'err_required', $this->getLabel(). ' ('. $this->getName() .')'));
            }
        } else {
            $this->has_errors = !$this->validate($artifact, $value);
        }
        return !$this->has_errors;
    }
    
    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact 
     * @param string           $value    data coming from the request. Should be artifact id separated by comma
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value) {
        $new_values = $value['new_values'];
        $is_valid = true;
        if (trim($new_values) != '') {
            $r = $this->getRuleArtifactId();
            $art_id_array = explode(',', $new_values);
            foreach ($art_id_array as $artifact_id) {
                $artifact_id = trim ($artifact_id);
                if ( ! $r->isValid($artifact_id)) {
                    $is_valid = false;
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_artifactlink_value', array($this->getLabel(), $artifact_id)));
                }
            }
        }
        return $is_valid;
    }
    
    public function getRuleArtifactId() {
        return new Tracker_Valid_Rule_ArtifactId();
    }
    
    /**
     * Returns an instance of Tracker_ArtifactFactory
     *
     * @return Tracker_ArtifactFactory 
     */
    public function getArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }
    
    public function getTrackerFactory() {
        return TrackerFactory::instance();
    }
    
    /**
     * Save the value and return the id
     * 
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value 
     * @param mixed                           $value                   The value submitted by the user
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
        $success = true;
        
        $new_values = (string)$value['new_values'];
        $removed_values = isset($value['removed_values']) ? $value['removed_values'] : array();
        
        // this array will be the one to save in the new changeset
        $artifact_ids = array();
        if ($previous_changesetvalue != null) {
            $artifact_ids = $previous_changesetvalue->getArtifactIds();
            // We remove artifact links that user wants to remove
            if (is_array($removed_values) && ! empty($removed_values)) {
                $artifact_ids = array_diff($artifact_ids, array_keys($removed_values));
            }
        }
        
        if (trim($new_values) != '') {
            $new_artifact_ids = array_diff(explode(',', $new_values), array_keys($removed_values));
            // We add new links to existing ones
            foreach ($new_artifact_ids as $new_artifact_id) {
                if ( ! in_array($new_artifact_id, $artifact_ids)) {
                    $artifact_ids[] = $new_artifact_id;
                }
            }
        }
        
        $dao = $this->getValueDao();
        // we create the new changeset
        foreach ($artifact_ids as $artifact_id) {
            $artifact_id = trim ($artifact_id);
            
            $af = $this->getArtifactFactory();
            $tf = $this->getTrackerFactory();
            $art = $af->getArtifactById($artifact_id);
            if ($art) {
                $tracker_id = $art->getTrackerId();
                $tracker = $tf->getTrackerById($tracker_id);
                if ($tracker) {
                    if  ( $dao->create($changeset_value_id, $artifact_id, $tracker->getItemName(), $tracker->getGroupId()) ) {
                        // extract cross references
                        $this->updateCrossReferences($artifact, $value);
                    } else {
                        $success = false;
                    }
                }
            }
        }
        return $success;
    }
    
    /**
     * Update cross references of this field
     *
     * @param Tracker_Artifact $artifact the artifact that is currently updated
     * @param array            $values   the array of added and removed artifact links ($values['added_values'] is a string and $values['removed_values'] is an array of artifact ids
     */
    private function updateCrossReferences($artifact, $values) {
        $added_artifact_ids = array();
        if (array_key_exists('new_values', $values)) {
            if (trim($values['new_values']) != '') {
                $added_artifact_ids = explode(',', $values['new_values']);
            }
        }
        $removed_artifact_ids = array();
        if (array_key_exists('removed_values', $values)) {
            $removed_artifact_ids = $values['removed_values'];
        }
        $af = Tracker_ArtifactFactory::instance();
        $rm = ReferenceManager::instance();
        foreach ($added_artifact_ids as $added_artifact_id) {
            $artifact_target = $af->getArtifactById((int)trim($added_artifact_id));
            $artifactlink = new Tracker_ArtifactLinkInfo(
                $artifact_target->getId(), 
                $artifact_target->getTracker()->getItemname(), 
                $artifact_target->getTracker()->getGroupId(),
                $artifact_target->getTracker()->getId(),
                $artifact_target->getLastChangeset()->getId()
            );
            $rm->extractCrossRef($artifactlink->getLabel(), $artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId(), UserManager::instance()->getCurrentUser()->getId(), $this->getTracker()->getItemName());
        }
        // TODO : remove the removed elements
    }
    
}
?>
