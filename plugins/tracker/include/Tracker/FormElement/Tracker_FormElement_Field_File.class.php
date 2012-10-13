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

require_once('Tracker_FormElement_Field.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_File.class.php');
require_once(dirname(__FILE__).'/../Report/dao/Tracker_Report_Criteria_File_ValueDao.class.php');
require_once('dao/Tracker_FormElement_Field_Value_FileDao.class.php');
require_once(dirname(__FILE__).'/../dao/Tracker_FileInfoDao.class.php');
require_once(dirname(__FILE__).'/../Tracker_FileInfo.class.php');
require_once('common/valid/Rule.class.php');

class Tracker_FormElement_Field_File extends Tracker_FormElement_Field {
    
    const THUMBNAILS_MAX_WIDTH  = 150;
    const THUMBNAILS_MAX_HEIGHT = 112;
    protected $supported_image_types = array('gif', 'png', 'jpeg', 'jpg');
    
    public function getCriteriaFrom($criteria) {
        //Only filter query if field  is used
        if($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a = 'A_'. $this->id;
                $b = 'B_'. $this->id;
                $c = 'C_'. $this->id;
                
                $da             = CodendiDataAccess::instance();
                $criteria_value = $da->quoteSmart("%$criteria_value%");
                
                return " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = $this->id )
                         INNER JOIN tracker_changeset_value_file AS $b ON ($b.changeset_value_id = $a.id)
                         INNER JOIN tracker_fileinfo AS $c ON (
                            $c.id = $b.fileinfo_id
                            AND (
                                $c.description LIKE ". $criteria_value ."
                                OR
                                $c.filename LIKE ". $criteria_value ."
                            )
                         ) ";
            }
        }
        return '';
    }
    
    public function getCriteriaWhere($criteria) {
        return '';
    }
    
    public function getQuerySelect() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return "$R2.fileinfo_id AS `". $this->name ."`";
    }
    
    public function getQueryFrom() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
       
        return "LEFT JOIN ( tracker_changeset_value AS $R1 
                    INNER JOIN tracker_changeset_value_file AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = ". $this->id ." )";
    }
    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return "$R2.fileinfo_id";
    }
    
    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_File_ValueDao();
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $html = '';
        $submitter_needed = true;
        $html .= $this->fetchAllAttachment($artifact_id, $this->getChangesetValues($changeset_id), $submitter_needed, array());
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
        return $this->fetchAllAttachmentForCSV($artifact_id, $this->getChangesetValues($changeset_id));
    }
    
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
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value) {
        return $value;
    }
    
    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        $value = '';
        if ($v = $changeset->getValue($this)) {
            if (isset($v['value_id'])) {
                $v = array($v);
            }
            foreach ($v as $val) {
                if ($val['value_id'] != 100) {
                    if ($row = $this->getValueDao()->searchById($val['value_id'], $this->id)->getRow()) {
                        if ($value) {
                            $value .= ', ';
                        }
                        $value .= $row['filename'];
                    }
                }
            }
        }
        return $value;
    }
    
    protected function getValueDao() {
        return new Tracker_FormElement_Field_Value_FileDao();
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
        $html             = '';
        $submitter_needed = true;
        $read_only        = false;
        $html .= $this->fetchAllAttachment($artifact->id, $value, $submitter_needed, $submitted_values, $read_only);
        $html .= $this->fetchSubmitValue();
        return $html;
    }
        
    /**
     * Fetch the html code to display the field value in Mail
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
        return $this->fetchMailAllAttachment($artifact->id, $value, $format);
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
        $html = '';
        $submitter_needed = true;
        $html .= $this->fetchAllAttachment($artifact->id, $value, $submitter_needed, array());
        return $html;
    }
    
    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue() {
        $html = '';
        $html .= '<div class="tracker_artifact_attachment">';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file').'</p>';
        $html .= '<table class="tracker_artifact_add_attachment">';
        $html .= '<tr><td><label>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file_description').'</label></td><td><label>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file_file').'</label></td></tr>';
        $html .= '<tr><td><input type="text" id="tracker_field_'. $this->id .'" name="artifact['. $this->id .'][][description]" /></td>';
        $html .= '<td><input type="file" id="tracker_field_'. $this->id .'" name="artifact['. $this->id .'][][file]" /></td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        return '';  // deactivate mass change for file fields (see issue described in rev #15855)
        
        $html = '';
        $html .= '<div class="tracker_artifact_attachment">';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file').'</p>';
        $html .= '<table class="tracker_artifact_add_attachment">';
        $html .= '<tr><td><label>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file_description').'</label></td><td><label>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file_file').'</label></td></tr>';
        $html .= '<tr><td><input type="text" id="tracker_field_'. $this->id .'" name="artifact['. $this->id .'][][description]" /></td>';
        $html .= '<td><input type="file" id="tracker_field_'. $this->id .'" name="artifact['. $this->id .'][][file]" /></td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }
    /**
     * Fetch the changes that has been made to this field in a followup
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param array            $from     the value(s) *before*
     * @param array            $to       the value(s) *after*
     *
     * @return string html
     */
    public function fetchFollowUp($artifact, $from, $to) {
        $html = '';
        //Retrieve all the values for the changeset
        $to_values = $this->getChangesetValues($to['changeset_id']);
        foreach ($to_values as $key => $v) {
            if (!$v['has_changed']) {
                unset($to_values[$key]);
            }
        }
        if (count($to_values)) {
            $submitter_needed = false;
            $html .= 'Added: '. $this->fetchAllAttachment($artifact->id, $to_values, $submitter_needed, array());
        }
        return $html;
    }
    
    protected function fetchAllAttachment($artifact_id, $values, $submitter_needed, $submitted_values, $read_only = true) {
        $html = '';
        if (count($values)) {
            $hp = Codendi_HTMLPurifier::instance();
            $uh = UserHelper::instance();
            $added = array();
            foreach ($values as $fileinfo) {
                $query_link = http_build_query(
                    array(
                        'aid'   => $artifact_id,
                        'field' => $this->id,
                        'func'  => 'show-attachment',
                        'attachment' => $fileinfo->getId()
                    )
                );
                $sanitized_description = $hp->purify($fileinfo->getDescription(), CODENDI_PURIFIER_CONVERT_HTML);
                $link_show = '<a href="'.TRACKER_BASE_URL.'/?'. $query_link .'" 
                                 '. ($fileinfo->isImage() ? 'rel="lytebox['. $this->getId() .']" ' : '') .'
                                 '. ($fileinfo->isImage() ? 'style="cursor:-moz-zoom-in;" '        : '') .'
                                 title="'. $sanitized_description .'">';
                
                $info = $link_show . $hp->purify($fileinfo->getFilename(), CODENDI_PURIFIER_CONVERT_HTML) .'</a>';
                $info .= ' ('. $fileinfo->getHumanReadableFilesize() .')';
                if ($submitter_needed) {
                    $info .= '<div class="tracker_artifact_attachment_submitter">'. 'By '. $uh->getLinkOnUserFromUserId($fileinfo->getSubmittedBy()) .'</div>';
                }
                
                $add = '<div class="tracker_artifact_attachment">';
                $add .= '<table><tr><td>';
                if (!$read_only) {
                    $add .= $this->fetchDeleteCheckbox($fileinfo, $submitted_values);
                    $add .= '</td><td>';
                }
                if ($fileinfo->isImage()) {
                    $query_add = http_build_query(
                        array(
                            'aid'   => $artifact_id,
                            'field' => $this->id,
                            'func'  => 'preview-attachment',
                            'attachment' => $fileinfo->getId()
                        )
                    );
                    $add .= $link_show;
                    $add .= '<span class="tracker_artifact_preview_attachment">';
                    $add .= '<img src="'.TRACKER_BASE_URL.'/?'. $query_add .'" 
                                  alt="'. $sanitized_description .'" 
                                  style="vertical-align:middle;" />';
                    $add .= '</span> ';
                    $add .= '</a>';
                    $add .= '</td><td>';
                }
                $add .= $info;
                $add .= '</td></tr></table>';
                $add .= '</div>';
                $added[] = $add;
            }
            $html .= implode('', $added);
        }
        return $html;
    }

    private function fetchDeleteCheckbox(Tracker_FileInfo $fileinfo, $submitted_values) {
        $html = '';
        $html .= '<label class="pc_checkbox tracker_artifact_attachment_delete">';
        $checked = '';
        if (!empty($submitted_values[0][$this->id]['delete']) && in_array($fileinfo->getId(), $submitted_values[0][$this->id]['delete'])) {
            $checked = 'checked="checked"';
        }
        $html .= '<input type="checkbox" name="artifact['. $this->id .'][delete][]" value="'. $fileinfo->getId() .'" title="delete" '. $checked .' />&nbsp;';
        $html .= '</label>';
        return $html;
    }

    protected function fetchAllAttachmentForCSV($artifact_id, $values) {
        $txt = '';
        if (count($values)) {
            $filenames = array();
            foreach ($values as $fileinfo) {
                $filenames[] = $fileinfo->getFilename();
            }
            $txt .= implode(',', $filenames);
        }
        return $txt;
    }

    /**
     * Fetch all attachements for Mail output
     *
     * @param Integer $artifact_id The artifact Id
     * @param Array            $values     The actual value of the field
     * @param String            $format       The mail format
     *
     * @return String
     */
    protected function fetchMailAllAttachment($artifact_id, $values, $format) {
        $output = '';
        if (!count($values) ) {
            return '';
        }

        $uh = UserHelper::instance();

        $proto = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $url = $proto .'://'. $GLOBALS['sys_default_domain'];

        if ($format == 'text') {
            foreach ($values as $fileinfo) {
                $query_link = http_build_query(
                array(
                        'aid'   => $artifact_id,
                        'field' => $this->id,
                        'func'  => 'show-attachment',
                        'attachment' => $fileinfo->getId()
                )
                );

                $link = '<'.$url.'/tracker?'.$query_link.'>';
                $output .= $fileinfo->getDescription();
                $output .= ' | ';
                $output .= $fileinfo->getFilename();
                $output .= ' | ';
                $output .= $fileinfo->getHumanReadableFilesize();
                $output .= ' | ';
                $output .= $uh->getDisplayNameFromUserId( $fileinfo->getSubmittedBy() );
                $output .= PHP_EOL;
                $output .= $link;
                $output .= PHP_EOL;
            }
        } else {
            $hp = Codendi_HTMLPurifier::instance();
            $added = array();
            foreach ($values as $fileinfo) {
                $query_link = http_build_query(
                array(
                        'aid'   => $artifact_id,
                        'field' => $this->id,
                        'func'  => 'show-attachment',
                        'attachment' => $fileinfo->getId()
                )
                );
                $sanitized_description = $hp->purify($fileinfo->getDescription(), CODENDI_PURIFIER_CONVERT_HTML);
                $link_show = '<a href="'.$url.TRACKER_BASE_URL.'/?'. $query_link .'"
                                 title="'. $sanitized_description .'">';

                $info = $link_show . $hp->purify($fileinfo->getFilename(), CODENDI_PURIFIER_CONVERT_HTML) .'</a>';
                $info .= ' ('. $fileinfo->getHumanReadableFilesize() .')';

                $add = '<div class="tracker_artifact_attachment">';
                $add .= '<table><tr><td>';
                $add .= $info;
                $add .= '</td></tr></table>';
                $add .= '</div>';
                $added[] = $add;
            }
            $output .= implode('', $added);
        }
        return $output;
    }

    protected $file_values_by_changeset;

    /**
     * @return array
     */
    protected function getChangesetValues($changeset_id) {
        $da = CodendiDataAccess::instance();
        if (!$this->file_values_by_changeset) {
            $this->file_values_by_changeset = array();
            $field_id     = $da->escapeInt($this->id);
            $sql = "SELECT c.changeset_id, c.has_changed, f.*
                    FROM tracker_fileinfo as f
                         INNER JOIN tracker_changeset_value_file AS vf on (f.id = vf.fileinfo_id)
                         INNER JOIN tracker_changeset_value AS c
                         ON ( vf.changeset_value_id = c.id
                          AND c.field_id = $field_id
                         )
                    ORDER BY f.id";
            $dao = new DataAccessObject();
            $values = array();
            foreach ($dao->retrieve($sql) as $row) {
                $this->file_values_by_changeset[$row['changeset_id']][] = $this->getFileInfo($row['id'], $row);
            }
        }
        return isset($this->file_values_by_changeset[$changeset_id]) ? $this->file_values_by_changeset[$changeset_id] : array();
    }
    
    public function previewAttachment($attachment_id) {
        if ($fileinfo = Tracker_FileInfo::instance($this, $attachment_id)) {
            if ($fileinfo->isImage() && file_exists($fileinfo->getThumbnailPath())) {
                header('Content-type: '. $fileinfo->getFiletype());
                readfile($fileinfo->getThumbnailPath());
            }
        }
        exit();
    }
    
    public function showAttachment($attachment_id) {
        if ($fileinfo = Tracker_FileInfo::instance($this, $attachment_id)) {
            if (file_exists($fileinfo->getPath())) {
                $http = Codendi_HTTPPurifier::instance();
                header('Content-Type: '.$http->purify($fileinfo->getFiletype()));
                header('Content-Length: '.$http->purify($fileinfo->getFilesize()));
                header('Content-Disposition: filename="'.$http->purify($fileinfo->getFilename()).'"');
                header('Content-Description: '. $http->purify($fileinfo->getDescription()));
                readfile($fileinfo->getPath());
            }
        }
        exit();
    }
    
    public function getRootPath() {
        return Config::get('sys_data_dir') .'/tracker/'. $this->getId();
    }
    
    protected function isImage($value) {
        $parts = split('/', $value['filetype']);
        return $parts[0] == 'image' && in_array(strtolower($parts[1]), $this->supported_image_types);
    }
    
    /**
     * Display the html field in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $html = '';
        $html .= '<div class="tracker_artifact_attachment">';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file').'</p>';
        $html .= '<table class="tracker_artifact_add_attachment">';
        $html .= '<tr><td><label>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file_description').'</label></td><td><label>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','add_new_file_file').'</label></td></tr>';
        $html .= '<tr><td><input type="text" id="tracker_field_'. $this->id .'" /></td>';
        $html .= '<td><input type="file" id="tracker_field_'. $this->id .'" /></td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','file');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','file_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/attach.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/attach--plus.png');
    }
    
    /**
     * Fetch the html code to display the field value in tooltip
     * 
     * @param Tracker_Artifact            $artifact The artifact
     * @param Tracker_ChangesetValue_File $value    The changeset value of this field
     *
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        if ($value) {
            $files_info = $value->getFiles();
            if (count($files_info)) {
                $hp = Codendi_HTMLPurifier::instance();
                
                $added = array();
                foreach ($files_info as $file_info) {
                    $add = '';
                    
                    if ($file_info->isImage()) {
                        $query = http_build_query(
                            array(
                                'aid'   => $artifact->id,
                                'field' => $this->id,
                                'func'  => 'preview-attachment',
                                'attachment' => $file_info->getId()
                            )
                        );
                        $add .= '<img src="'.TRACKER_BASE_URL.'/?'. $query .'" 
                                      alt="'.  $hp->purify($file_info->getDescription(), CODENDI_PURIFIER_CONVERT_HTML)  .'" 
                                      style="vertical-align:middle;" />';
                    } else if ($file_info->getDescription()) {
                        $add .= $hp->purify($file_info->getDescription(), CODENDI_PURIFIER_CONVERT_HTML);
                    } else {
                        $add .= $hp->purify($file_info->getFilename(), CODENDI_PURIFIER_CONVERT_HTML);
                    }
                    $added[] = $add;
                }
                $html .= implode('<br />', $added);
            }
        }
        return $html;
    }
    
    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact 
     * @param mixed            $value    data coming from the request. 
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value) {

        
        // TODO : implement
        
        
        
        return true;
        
        
    }
    
    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array. 
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Tracker_Artifact $artifact, $value) {
        $this->has_errors = false;
        
        if (is_array($value)) {
            //check required
            if ($this->isRequired()) {
                //check that there is at least one file uploaded
                if ( ! $this->checkThatAtLeastOneFileIsUploaded($value)) {
                    $this->has_errors = true;
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'err_required', $this->getLabel(). ' ('. $this->getName() .')'));
                }
            }
            
            //check that all files have been successfully uploaded
            if (!$this->has_errors) {
                $r = new Rule_File();
                foreach($value as $i => $attachment) {
                    //is delete or no description and no file uploaded => ignore it
                    if ( "$i" != 'delete' && ((!empty($attachment['error']) && $attachment['error'] != UPLOAD_ERR_NO_FILE) || trim($attachment['description']))) {
                        if (!$r->isValid($attachment)) {
                            $this->has_errors = true;
                            $GLOBALS['Response']->addFeedback('error', $this->getLabel() .' #'. $i .' has error: '. $r->getErrorMessage());
                        }
                    }
                }
            }
        } else {
            //TODO: WTF?
        }
        return !$this->has_errors;
    }
    
    /**
     * Check that at least one file is sent
     *
     * @param array $files the files
     *
     * @return bool true if success
     */
    public function checkThatAtLeastOneFileIsUploaded($files) {
        $r = new Rule_File();
        $a_file_is_sent = false;
        reset($files);
        while (!$a_file_is_sent && (list($action, $attachment) = each($files))) {
            if ("$action" != 'delete') {
                $a_file_is_sent = $r->isValid($attachment);
            }
        }
        return $a_file_is_sent;
    }
    
    /**
     * Extract data from request
     * Some fields like files doesn't have their value submitted in POST or GET
     * Let them populate $fields_data[field_id] if needed
     *
     * @param array &$fields_data The user submitted value
     *
     * @return void
     */
    public function augmentDataFromRequest(&$fields_data) {
        if (!isset($fields_data[$this->getId()]) || !is_array($fields_data[$this->getId()])) {
            $fields_data[$this->getId()] = array();
        }
        $files_infos = $this->getSubmittedInfoFromFILES();
        if (isset($files_infos['name'][$this->getId()])) {
            $info_keys = array_keys($files_infos); //name, type, error, ...
            $nb = count($files_infos['name'][$this->getId()]);
            for ($i = 0 ; $i < $nb ; ++$i) {
                $tab = array();
                foreach ($info_keys as $key) {
                    $tab[$key] = $files_infos[$key][$this->getId()][$i]['file'];
                }
                if (isset($fields_data[$this->getId()][$i])) {
                    $fields_data[$this->getId()][$i] = array_merge($fields_data[$this->getId()][$i], $tab);
                } else {
                    $fields_data[$this->getId()][] = $tab;
                }
            }
        }
    }
    
    /**
     * Get the array wich contains files submitted by the user
     *
     * @return array or null if not found
     */
    protected function getSubmittedInfoFromFILES() {
        return isset($_FILES['artifact']) ? $_FILES['artifact'] : null;
    }
    
    protected $files_info_from_request = null;
    /**
     * Extract the file information (name, error, tmp, ...) from the request
     *
     * @return array Array of file info
     */
    protected function extractFilesFromRequest() {
        if (!$this->files_info_from_request) {
        }
        return $this->files_info_from_request;
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
        $success = array();
        $dao = $this->getValueDao();
        //first save the previous files
        if ($previous_changesetvalue) {
            $previous_fileinfo_ids = array();
            foreach($previous_changesetvalue as $previous_attachment) {
                if (empty($value['delete']) || !in_array($previous_attachment->getId(), $value['delete'])) {
                    $previous_fileinfo_ids[] = $previous_attachment->getId();
                }
            }
            if (count($previous_fileinfo_ids)) {
                $dao->create($changeset_value_id, $previous_fileinfo_ids);
            }
        }
        
        //Now save the new submitted files
        $current_user = UserManager::instance()->getCurrentUser();
        $r = new Rule_File();
        foreach ($value as $i => $file_info) {
            if ("$i" != 'delete' && $r->isValid($file_info)) {
                if ($attachment = Tracker_FileInfo::create($this, $current_user->getId(), trim($file_info['description']), $file_info['name'], $file_info['size'], $file_info['type'])) {
                    $path = $this->getRootPath();
                    if (!is_dir($path .'/thumbnails')) {
                        mkdir($path .'/thumbnails', 0777, true);
                    }
                    $filename = $path .'/'. $attachment->getId();
                    if (move_uploaded_file($file_info['tmp_name'], $filename)) {
                        $success[] = $attachment->getId();
                        //If image, store thumbnails
                        if ($attachment->isImage()) {
                            $this->createThumbnail($attachment->getId(), $path, $filename);
                        }
                    } else {
                        //Something goes wrong
                        //delete the attachment
                        $attachment->delete();
                    }
                }
            }
        }
        if (count($success)) {
            $dao->create($changeset_value_id, $success);
        }
        return $success;
    }
    
    /**
     * Create a thumbnail of the image
     * 
     * @param integer $attachment_id The id of the attachment
     * @param string  $path          The path of the thumbnail. Assume that the dir exists.
     * @param string  $filename      The name of the file
     *
     * @return void
     */
    public function createThumbnail($attachment_id, $path, $filename) {
        //
        // All modifications to this script should be done in the migration script 125
        //
        $size = getimagesize($filename);
        $thumbnail_width  = $size[0];
        $thumbnail_height = $size[1];
        if ($thumbnail_width > self::THUMBNAILS_MAX_WIDTH || $thumbnail_height > self::THUMBNAILS_MAX_HEIGHT) { 
            if ($thumbnail_width / self::THUMBNAILS_MAX_WIDTH < $thumbnail_height / self::THUMBNAILS_MAX_HEIGHT) {
                //keep the height
                $thumbnail_width  = $thumbnail_width * self::THUMBNAILS_MAX_HEIGHT / $thumbnail_height;
                $thumbnail_height = self::THUMBNAILS_MAX_HEIGHT;
            } else {
                //keep the width
                $thumbnail_height = $thumbnail_height * self::THUMBNAILS_MAX_WIDTH / $thumbnail_width;
                $thumbnail_width  = self::THUMBNAILS_MAX_WIDTH;
            }
        }
        switch ($size[2]) {
        case IMAGETYPE_GIF:
            $source      = imagecreatefromgif($filename);
            $destination = imagecreate((int)$thumbnail_width, (int)$thumbnail_height);
            imagepalettecopy($destination, $source);
            $store       = 'imagegif';
            break;
        case IMAGETYPE_JPEG:
            $source      = imagecreatefromjpeg($filename);
            $destination = imagecreatetruecolor((int)$thumbnail_width, (int)$thumbnail_height);
            $store       = 'imagejpeg';
            break;
        case IMAGETYPE_PNG:
            $source      = imagecreatefrompng($filename);
            $destination = imagecreatetruecolor((int)$thumbnail_width, (int)$thumbnail_height);
            $store       = 'imagepng';
            break;
        }
        imagecopyresized($destination, $source, 0, 0, 0, 0, (int)$thumbnail_width, (int)$thumbnail_height, $size[0], $size[1]);
        $store($destination, $path .'/thumbnails/'. $attachment_id);
        imagedestroy($source);
        imagedestroy($destination);
    }
    
    /**
     * Check if there are changes between old and new value for this field
     *
     * @param Tracker_Artifact_ChangesetValue $old_value The data stored in the db
     * @param mixed                           $new_value May be string or array
     *
     * @return bool true if there are differences
     */
    public function hasChanges(Tracker_Artifact_ChangesetValue $old_value, $new_value) {
        //"old" and "new" value are irrelevant in this context.
        //We just have to know if there is at least one file successfully uploaded
        return $this->checkThatAtLeastOneFileIsUploaded($new_value) || !empty($new_value['delete']);
    }
    
    /**
     * Tells if the field takes two columns
     * Ugly legacy hack to display fields in columns
     *
     * @return boolean
     */
    public function takesTwoColumns() {
        return true;
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
        
        $files = array();
        $file_value = $this->getValueDao()->searchById($value_id, $this->id);
        foreach ($file_value as $row) {
            if ($fileinfo_row = $this->getFileInfoDao()->searchById($row['fileinfo_id'])->getRow()) {
                $files[] = $this->getFileInfo($fileinfo_row['id'], $fileinfo_row);
            }
        }
        $changeset_value = new Tracker_Artifact_ChangesetValue_File($value_id, $this, $has_changed, $files);
        return $changeset_value;
    }
    
    /**
     * @return Tracker_FileInfo
     */
    protected function getFileInfo($id, $row) {
        return Tracker_FileInfo::instance($this, $id, $row);
    }
    
    /**
     * Get the file dao
     * 
     * @return Tracker_FileInfoDao
     */
    protected function getFileInfoDao() {
        return new Tracker_FileInfoDao();
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
     * Get the field data for CSV import
     *
     * @param string $data_cell the CSV field value (a date with the form dd/mm/YYYY or mm/dd/YYYY)
     *
     * @return string the date with the form YYYY-mm-dd corresponding to the date $data_cell
     */
    public function getFieldDataForCSVPreview($data_cell) {
        return $data_cell;
    }
    
    /**
     * Get the field data for artifact submission
     *
     * @param string the soap field value
     *
     * @return String the field data corresponding to the soap_value for artifact submision
     */
    public function getFieldData($soap_value) {
        // files can't be imported. Always return an empty array
        return array();
    }

    public function deleteChangesetValue($changeset_value_id) {
        $values = $this->getChangesetValue(null, $changeset_value_id, false);
        foreach($values as $fileinfo) {
            $fileinfo->delete();
        }
        parent::deleteChangesetValue($changeset_value_id);
    }
}
?>
