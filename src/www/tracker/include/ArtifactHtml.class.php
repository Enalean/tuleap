<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// 
//
//
//      Originally by to the SourceForge Team,1999-2000
//
//  Parts of code come from bug_util.php (written by Laurent Julliard)
//
//  Written for CodeX by Stephane Bouhet
//
require_once('common/tracker/Artifact.class.php');
require_once('common/mail/Mail.class.php');
require_once('common/include/ReferenceManager.class.php');
require_once('javascript_helpers.php');

$Language->loadLanguageMsg('tracker/tracker');

class ArtifactHtml extends Artifact {

        /**
         *  ArtifactHtml() - constructor
         *
         *  Use this constructor if you are modifying an existing artifact
         *
         *  @param $ArtifactType object
         *  @param $artifact_id integer (primary key from database)
         *  @return true/false
         */
        function ArtifactHtml(&$ArtifactType,$artifact_id=false) {
                return $this->Artifact($ArtifactType,$artifact_id);
        }

        /**
         * Display the artifact
         *
         * @param ro: read only parameter - Display mode or update mode
         * @param pv: printer version
         *
         * @return void
         */
        function display($ro, $pv, $user_id) {
            global $art_field_fact,$art_fieldset_fact,$sys_datefmt,$sys_max_size_attachment,$Language;
            
            $fields_per_line=2;
            // the column number is the number of field per line * 2 (label + value)
            // + the number of field per line -1 (a blank column between each pair "label-value" to give more space)
            $columns_number = ($fields_per_line * 2) + ($fields_per_line - 1);
            $max_size=40;
            
            $group = $this->ArtifactType->getGroup();
            $group_artifact_id = $this->ArtifactType->getID();
            $group_id = $group->getGroupId();
            $result_fields = $art_field_fact->getAllUsedFields();

            
            $result_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
            
            
            // First display some  internal fields 
            $summary = $this->getValue('summary');
            echo '<script type="text/javascript" src="/scripts/calendar_js.php"></script>';
            echo '
            <FORM ACTION="'.$_SERVER['PHP_SELF'].'" METHOD="POST" enctype="multipart/form-data" NAME="artifact_form">
            <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_attachment.'">';
            if ( $ro ) {
                echo '<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddcomment">';
            } else {
                echo '<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmod">';
            }
            echo '
            <INPUT TYPE="HIDDEN" NAME="artifact_timestamp" VALUE="'.time().'">
            <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
            <INPUT TYPE="HIDDEN" NAME="group_artifact_id" VALUE="'.$group_artifact_id.'">
            <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.$group_artifact_id.'">
            <INPUT TYPE="HIDDEN" NAME="artifact_id" VALUE="'.$this->getID().'">
            <INPUT TYPE="HIDDEN" NAME="aid" VALUE="'.$this->getID().'">';
            echo '<TABLE><TR><TD class="artifact">';
            
            echo '<table width="100%"><tr><td><H2>[ '.$this->ArtifactType->getItemName();
            $field_artifact_id = $result_fields['artifact_id'];
            if ($field_artifact_id->userCanRead($group_id, $group_artifact_id, $user_id)) {
                echo " #".$this->getID();
            }
            echo " ] ".$summary."</H2>";
            echo "</TD>";
            
            if ( $pv == 0) {
                echo "<TD align='right'><A HREF='?func=detail&aid=".$this->getID()."&group_id=".$group_id."&atid=".$group_artifact_id."&pv=1' target='_blank'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A></TD></TR>";
            }
            echo '</table>';
            
            if ($this->ArtifactType->allowsCopy()) {
              echo "<div><A HREF='?func=copy&aid=".$this->getID()."&group_id=".$group_id."&atid=".$group_artifact_id."'>".$Language->getText('tracker_include_artifact','copy_art')."</A></div><br />";
            }
        
            $html = '';
            $html .= '<TABLE width="100%"><TR>';
            $html .= '<TD align="left"><B>'.$Language->getText('tracker_include_artifact','project').'</B>&nbsp;</td><td COLSPAN="'.($columns_number-1).'">'.group_getname($group_id).'</TD>';
            
            // Now display the variable part of the field list (depend on the project)
            
            foreach($result_fieldsets as $fieldset_id => $result_fieldset) {
                
                // this variable will tell us if we have to display the fieldset or not (if there is at least one field to display or not)
                $display_fieldset = false;
                
                $fieldset_html = '';
                
                $i = 0;
                $fields_in_fieldset = $result_fieldset->getAllUsedFields();
                while ( list($key, $field) = each($fields_in_fieldset) ) {
                    if ($field->getName() != 'comment_type_id' && $field->getName() != 'artifact_id') {
                        $field_html = $this->_getFieldLabelAndValueForUser($group_id, $group_artifact_id, $field, $user_id, $pv);
                        if ($field_html) {
                            
                            // if the user can read at least one field, we can display the fieldset this field is within
                            $display_fieldset = true;
                            
                            list($sz,) = explode("/",$field->getDisplaySize());
                        
                            // Details field must be on one row
                            if ($sz > $max_size || $field->getName()=='details') {
                                $fieldset_html .= "\n<TR>".
                                  '<TD align="left" valign="top" width="10" nowrap="nowrap">'.$field_html['label'].'</td>'.
                                  '<TD valign="top" colspan="'.($columns_number-1).'">'.$field_html['value'].'</TD>'.                     
                                  "\n</TR>";
                                $i=0;
                            } else {
                                $fieldset_html .= ($i % $fields_per_line ? '':"\n<TR>");
                                $fieldset_html .= '<TD align="left" valign="top" width="10" nowrap="nowrap">'.$field_html['label'].'</td>'.
                                '<TD valign="top">'.$field_html['value'].'</TD>';
                                $i++;
                                // if the line is not full, we add a additional column to give more space
                                $fieldset_html .= ($i % $fields_per_line) ? '<td class="artifact_spacer">&nbsp;</td>':"\n</TR>";
                            }
                        }
                    }
                } // while
                
                // We display the fieldset only if there is at least one field inside that we can display
                if ($display_fieldset) {
                    $html .= '<TR><TD COLSPAN="'.$columns_number.'">&nbsp</TD></TR>';
                    $html .= '<TR class="boxtitle"><TD class="left" COLSPAN="'.$columns_number.'">&nbsp;<span title="'.$result_fieldset->getDescriptionText().'">'.$result_fieldset->getLabel().'</span></TD></TR>';
                    $html .= $fieldset_html;
                }

            }
            
            $html .= '<tr><td><p><font color="red">*</font>: '.
                 $Language->getText('tracker_include_type','fields_requ').
                 '</p></td></tr></TABLE>';
            
            echo $this->_getSection(
                'artifact_section_details',
                'Details',
                $html,
                true
            );

            if (!$ro) {
                echo '<div style="text-align:center"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_artifact','submit').'"></div>';
            }
            //
            // Followups comments
            //
            $html = '';
            $html .= '<script type="text/javascript">';
            $html .= "function tracker_reorder_followups() {
                var element = $('artifact_section_followups');
                if (element) {
                    element.cleanWhitespace();
                    var elements = [];
                    var len = element.childNodes.length;
                    for(var i = len - 1 ; i >= 0 ; --i) {
                        elements.push(Element.remove(element.childNodes[i]));
                    }
                    for(var i = 0 ; i < len ; ++i) {
                        element.appendChild(elements[i]);
                    }
                }
            }";
            $html .= '</script>';
            $html .= '<div>';
            if ( !$ro ) {
                if (db_numrows($this->ArtifactType->getCannedResponses())) {
                    $html .= '<p><b>'.$Language->getText('tracker_include_artifact','use_canned').'</b>&nbsp;';
                    $html .= $this->ArtifactType->cannedResponseBox ();
                    $html .= '</p>';
                }
                $field = $art_field_fact->getFieldFromName('comment_type_id');
                if ( $field && $field->isUsed() && db_numrows($field->getFieldPredefinedValues($group_artifact_id)) > 1) {
                    $field_html = new ArtifactFieldHtml( $field );
                    $html .= '<P><B>'.$Language->getText('tracker_include_artifact','comment_type').'</B>'.
                    $field_html->fieldBox('',$group_artifact_id,$field->getDefaultValue(),true,$Language->getText('global','none')).'<BR>';
                }
                $html .= '<TEXTAREA NAME="comment" ROWS="7" COLS="80" WRAP="SOFT"></TEXTAREA>';
            } else {
                if ($pv == 0) {
                    $html .= '<b>'.$Language->getText('tracker_include_artifact','add_comment').'</b>';
                    $html .= '<TEXTAREA NAME="comment" ROWS="7" COLS="60" WRAP="SOFT"></TEXTAREA>';
                }
            }
            if (!user_isloggedin() && ($pv == 0)) {
                $html .= $Language->getText('tracker_include_artifact','not_logged_in','/account/login.php?return_to='.urlencode($_SERVER['REQUEST_URI']));
                $html .= '<br><input type="text" name="email" maxsize="100" size="50"/><p>';
            }
            $html .= '</div>';
            $html .= "<br />";
            $html .=  $this->showFollowUpComments($group_id,$pv);
            
            $title  = $Language->getText('tracker_include_artifact','follow_ups').' ';
            $title .= help_button('ArtifactUpdate.html#ArtifactComments') .' ';
            $title .= '<script type="text/javascript">';
            $title .= 'document.write(\'<a href="#reorder" onclick="tracker_reorder_followups();return false;" title="Invert order of the follow-ups">[&darr;&uarr;]</a>\');';
            $title .= '</script>';
            echo $this->_getSection(
                'artifact_section_followups',
                $title,
                $html,
                true
            );
            
            //
            // CC List
            //
            $html = '';
            if ($pv == 0) {
                $html .= $Language->getText('tracker_include_artifact','fill_cc_list_msg');
                $html .= $Language->getText('tracker_include_artifact','fill_cc_list_lbl');
                $html .= '<input type="text" name="add_cc" id="tracker_cc" size="30">';
                $html .= '<B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').":&nbsp</b>";
                $html .= '<input type="text" name="cc_comment" size="40" maxlength="255">';
                $html .= autocomplete_for_lists_users('tracker_cc', 'tracker_cc_autocomplete');
            }
            $html .= $this->showCCList($group_id,$group_artifact_id);
            
            echo $this->_getSection(
                'artifact_section_cc',
                $Language->getText('tracker_include_artifact','cc_list').' '. help_button('ArtifactUpdate.html#ArtifactCCList'),
                $html,
                db_numrows($this->getCCList()),
                db_numrows($this->getCCList()) ? '' : '<em>'. $GLOBALS['Language']->getText('tracker_include_artifact','cc_empty') .'</em>'
            );
                    
            //
            // File attachments
            //
            $html = '';
            if ($pv == 0) {
                $html .= $Language->getText('tracker_include_artifact','upload_checkbox');
                $html .= ' <input type="checkbox" name="add_file" VALUE="1">';
                $html .= $Language->getText('tracker_include_artifact','upload_file_lbl');
                $html .= '<input type="file" name="input_file" size="40">';
                $html .= $Language->getText('tracker_include_artifact','upload_file_msg',formatByteToMb($sys_max_size_attachment));

                $html .= $Language->getText('tracker_include_artifact','upload_file_desc');
                $html .= '<input type="text" name="file_description" size="60" maxlength="255">';
            }
            $html .= $this->showAttachedFiles($group_id,$group_artifact_id);
            
            echo $this->_getSection(
                'artifact_section_attachments',
                $Language->getText('tracker_include_artifact','attachment').' '. help_button('ArtifactUpdate.html#ArtifactAttachments'),
                $html,
                db_numrows($this->getAttachedFiles()),
                db_numrows($this->getAttachedFiles()) ? '' : '<em>'. $GLOBALS['Language']->getText('tracker_include_artifact','no_file_attached') .'</em>'
            );

            //
            // Artifact dependencies
            //
            $html = '<B>'.$Language->getText('tracker_include_artifact','depend_on').'</B><BR><P>';
            if ( !$ro ) {
                    $html .= '
                    <B>'.$Language->getText('tracker_include_artifact','aids').'</B>&nbsp;
                    <input type="text" name="artifact_id_dependent" size="20" maxlength="255">
                    &nbsp;<i>'.$Language->getText('tracker_include_artifact','fill').'</i><p>';
            }
            $html .=  $this->showDependencies($group_id,$group_artifact_id);
            
            $html .= '
            <P><B>'.$Language->getText('tracker_include_artifact','dependent_on').'</B><BR>
            <P>';
            $html .= $this->showInverseDependencies($group_id,$group_artifact_id);
            echo $this->_getSection(
                'artifact_section_dependencies',
                $Language->getText('tracker_include_artifact','dependencies').' '.help_button('ArtifactUpdate.html#ArtifactDependencies'),
                $html,
                db_numrows($this->getDependencies()),
                db_numrows($this->getDependencies()) ? '' : '<em>'. $Language->getText('tracker_include_artifact','dep_list_empty') .'</em>'
            );
            
            //
            // History
            //
            echo $this->_getSection(
                'artifact_section_history', 
                $Language->getText('tracker_include_artifact','change_history').' '.help_button('ArtifactUpdate.html#ArtifactHistory'),
                $this->showHistory($group_id,$group_artifact_id),
                false
            );
            
            // 
            // Final submit button
            //
            if ( $pv == 0) {
                echo '<div style="text-align:center"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_artifact','submit').'"></div>';
            }
            echo '</td></tr>';
            echo '</table>';
            echo '</form>';
        }
        
        /**
        * _getSection
        * 
        * Display an html toggable fieldset
        *
        * @param  title  
        * @param  content  
        * @param  help  
        */
        function _getSection($id, $title, $content, $is_open, $alternate_text = '') {
            $html =  '<fieldset><legend>';
            if ($is_open) {
                $sign = 'minus';
                $display = '';
            } else {
                $sign = 'plus';
                $display = 'display:none;';
            }
            $html .= $GLOBALS['HTML']->getImage('ic/toggle_'. $sign .'.png', array('border' => 0, 'id' => $id.'_toggle', 'style' => 'cursor:hand; cursor:pointer'));
            $html .= $title .'</legend><div id="'. $id .'_alternate" style="display:none;"></div>';
            $html .= '<script type="text/javascript">';
            $html .= "Event.observe($('". $id ."_toggle'), 'click', function (evt) {
                var element = $('$id');
                if (element) {
                    Element.toggle(element);
                    Element.toggle($('". $id ."_alternate'));
                    
                    //replace image
                    var src_search = 'toggle_minus';
                    var src_replace = 'toggle_plus';
                    if ($('". $id ."_toggle').src.match('toggle_plus')) {
                        src_search = 'toggle_plus';
                        src_replace = 'toggle_minus';
                    }
                    $('". $id ."_toggle').src = $('". $id ."_toggle').src.replace(src_search, src_replace);
                }
                Event.stop(evt);
                return false;
            });
            $('". $id ."_alternate').update('". addslashes($alternate_text) ."');
            ";
            if (!$is_open) {
                $html .= "Element.show($('". $id ."_alternate'));";
            }
            $html .= '</script>';
            $html .= '<div id="'. $id .'" style="'. $display .'">'. $content .'</div></fieldset>';
            return $html;
        }
        
        
        /**
         * return a field for the given user.
         * 
         * @protected
        **/
        function _getFieldLabelAndValueForUser($group_id, $group_artifact_id, &$field, $user_id, $force_read_only = false) {
            $html = false;
            if ($field->userCanRead($group_id, $group_artifact_id, $user_id)) {
                $read_only =  $force_read_only || !$field->userCanUpdate($group_id, $group_artifact_id, $user_id);
                
                // For multi select box, we need to retrieve all the values
                if ( $field->isMultiSelectBox() ) {
                        $field_value = $field->getValues($this->getID());
                } else {
                        $field_value = $this->getValue($field->getName());
                }
                
                $field_html  =& new ArtifactFieldHtml($field);
                $label       = $field_html->labelDisplay(false,false,!$read_only);
                $label      .= ($field->isEmptyOk() ? '':'<span class="highlight"><big>*</big></b></span>');
                
                // original submission field must be displayed read-only,
                // except for site admin, tracker admin and for the artifact submitter
                if ($field->getName()=='details') {
                    if (user_is_super_user() || $this->ArtifactType->userIsAdmin() || $this->getSubmittedBy()==$user_id ) {
                        // original submission is editable
                        $value = $field_html->display($this->ArtifactType->getID(),$field_value,false,false,$read_only);
                    } else {
                        $value = util_make_links($field_html->display($this->ArtifactType->getID(),$field_value,false,false,true),$group_id, $group_artifact_id);
                    }
                } else if ($field->getName() == 'submitted_by') {
                    $value = util_user_link(user_getname($field_value));
                } else if ($field->getName() == 'open_date') {
                    $value = format_date($GLOBALS['sys_datefmt'],$field_value);
                } else {
                    $value = $field_html->display($this->ArtifactType->getID(),$field_value,false,false,$read_only);
                    if ($read_only) $value = util_make_links($value,$group_id, $group_artifact_id);
                }
                
                $html = array('label' => $label, 'value' => $value);
            }
            return $html;
        }

    /**
     * Display the artifact
     *
     * @param ro: read only parameter - Display mode or update mode
     * @param pv: printer version
     *
     * @return void
     */
    function displayCopy($ro,$pv) {
        global $art_field_fact,$art_fieldset_fact,$sys_datefmt,$sys_max_size_attachment,$Language;
          
        $fields_per_line=2;
        // the column number is the number of field per line * 2 (label + value)
        // + the number of field per line -1 (a blank column between each pair "label-value" to give more space)
        $columns_number = ($fields_per_line * 2) + ($fields_per_line - 1);
        $max_size=40;
          
        $group = $this->ArtifactType->getGroup();
        $group_artifact_id = $this->ArtifactType->getID();
        $group_id = $group->getGroupId();
        $result_fields = $art_field_fact->getAllUsedFields();
        $result_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
            
        
        // Display submit informations if any
        if ( $this->ArtifactType->getSubmitInstructions() ) {
            echo util_unconvert_htmlspecialchars($this->ArtifactType->getSubmitInstructions());
        }
        
        // Beginning of the submission form with fixed fields
        echo '<FORM ACTION="'.$_SERVER['PHP_SELF'].'" METHOD="POST" enctype="multipart/form-data" NAME="artifact_form">
                <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_attachment.'">
                <INPUT TYPE="HIDDEN" NAME="func" VALUE="postcopy">
                <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
                <INPUT TYPE="HIDDEN" NAME="group_artifact_id" VALUE="'.$group_artifact_id.'">
                <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.$group_artifact_id.'">
                <script type="text/javascript" src="/scripts/calendar_js.php"></script>';
        echo '<TABLE><TR><TD class="artifact">';
        $summary = $this->getValue('summary');
          
        echo "<TABLE width='100%'><TR><TD>";
        echo "<H2>[ ".$Language->getText('tracker_include_artifact','copy_of',$this->ArtifactType->getItemName()." #".$this->getID())." ] ".$summary."</H2>";
        echo "</TD></TR></TABLE>";
          
        $html = '';
        $html .= '
            <table width="100%">
              <tr><td colspan="'.$columns_number.'"><B>'.$Language->getText('tracker_include_artifact','group').':</B>&nbsp;'.group_getname($group_id).'</TD></tr>';
        
        // Now display the variable part of the field list (depend on the project)
        
        foreach($result_fieldsets as $fieldset_id => $result_fieldset) {
                
            // this variable will tell us if we have to display the fieldset or not (if there is at least one field to display or not)
            $display_fieldset = false;
            
            $fieldset_html = '';
            
            $i = 0;
            $fields_in_fieldset = $result_fieldset->getAllUsedFields();
            
            while ( list($key, $field) = each($fields_in_fieldset) ) {
    
                $field_html = new ArtifactFieldHtml($field);
                //echo $field_html->dumpStandard()."<br>";
                    
                // if the field is a special field (except summary and details) 
                // then skip it.
                if ( $field->userCanSubmit($group_id, $group_artifact_id) && (!$field->isSpecial() || $field->getName()=='summary' || $field->getName()=='details' )) {
                  
                    // display the artifact field
                    // if field size is greatest than max_size chars then force it to
                    // appear alone on a new line or it won't fit in the page
                    
                    $display_fieldset = true;
                    
                    // For multi select box, we need to retrieve all the values
                    if ( $field->isMultiSelectBox() ) {
                        $field_value = $field->getValues($this->getID());
                    } else {
                        if ($field->getName()=='summary') {
                            $field_value = '['.$Language->getText('tracker_include_artifact','copy').'] '.$this->getValue($field->getName());
                        } else {
                            $field_value = $this->getValue($field->getName());
                        }
                    }
                          
                    list($sz,) = explode("/",$field->getDisplaySize());
                    $label = $field_html->labelDisplay(false,false,!$ro);
                    $value = $field_html->display($this->ArtifactType->getID(),$field_value,false,false,$ro);
                          
                    $star = ($field->isEmptyOk() ? '':'<span class="highlight"><big>*</big></b></span>');
                          
                    // Details field must be on one row
                    if ($sz > $max_size || $field->getName()=='details') {
                        $fieldset_html .= "\n<TR>".
                        '<TD valign="middle">'.$label.$star.'</td>'.
                        '<TD valign="middle" colspan="'.($columns_number-1).'">'.
                        $value.'</TD>'.                     
                        "\n</TR>";
                        $i=0;
                    } else {
                        $fieldset_html .= ($i % $fields_per_line ? '':"\n<TR>");
                        $fieldset_html .= '<TD valign="middle">'.$label.$star.'</td>'.
                        '<TD valign="middle">'.$value.'</TD>';
                        $i++;
                        $fieldset_html .= ($i % $fields_per_line ? '<td class="artifact_spacer">&nbsp;</td>':"\n</TR>");
                    }
                }
            } // while
            
            // We display the fieldset only if there is at least one field inside that we can display
            if ($display_fieldset) {
                $html .= '<TR><TD COLSPAN="'.$columns_number.'">&nbsp</TD></TR>';
                $html .= '<TR class="boxtitle"><TD class="left" COLSPAN="'.$columns_number.'">&nbsp;<span title="'.$result_fieldset->getDescriptionText().'">'.$result_fieldset->getLabel().'</span></TD></TR>';
                $html .= $fieldset_html;
            }
            
        }
        
        $html .= '</TABLE>';
        
        echo $this->_getSection(
            'artifact_section_details',
            'Details',
            $html,
            true
        );
        
        //
        // Followups comments
        //
        $html = '';
        $html .= '<div>';
        if ( !$ro ) {
            if (db_numrows($this->ArtifactType->getCannedResponses())) {
                $html .= '<p><b>'.$Language->getText('tracker_include_artifact','use_canned').'</b>&nbsp;';
                $html .= $this->ArtifactType->cannedResponseBox ();
                $html .= '</p>';
            }
            $field = $art_field_fact->getFieldFromName('comment_type_id');
            if ( $field && $field->isUsed() && db_numrows($field->getFieldPredefinedValues($group_artifact_id)) > 1) {
                $field_html = new ArtifactFieldHtml( $field );
                $html .= '<P><B>'.$Language->getText('tracker_include_artifact','comment_type').'</B>'.
                $field_html->fieldBox('',$group_artifact_id,$field->getDefaultValue(),true,$Language->getText('global','none')).'<BR>';
            }
            $html .= '<TEXTAREA NAME="follow_up_comment" ROWS="7" COLS="80" WRAP="SOFT">'.$Language->getText('tracker_include_artifact','is_copy',array($this->ArtifactType->getItemName(),$this->ArtifactType->getItemName().' #'.$this->getID())).'</TEXTAREA>';
        } else {
            if ($pv == 0) {
                $html .= '<b>'.$Language->getText('tracker_include_artifact','add_comment').'</b>';
                $html .= '<TEXTAREA NAME="follow_up_comment" ROWS="7" COLS="60" WRAP="SOFT">'.$Language->getText('tracker_include_artifact','is_copy',array($this->ArtifactType->getItemName(),$this->ArtifactType->getItemName().' #'.$this->getID())).'</TEXTAREA>';
            }
        }
        if (!user_isloggedin() && ($pv == 0)) {
            $html .= $Language->getText('tracker_include_artifact','not_logged_in','/account/login.php?return_to='.urlencode($_SERVER['REQUEST_URI']));
            $html .= '<br><input type="text" name="email" maxsize="100" size="50"/><p>';
        }
        $html .= '</div>';
        $html .= "<br />";
        
        $title  = $Language->getText('tracker_include_artifact','follow_ups').' ';
        $title .= help_button('ArtifactUpdate.html#ArtifactComments');
        echo $this->_getSection(
            'artifact_section_followups',
            $title,
            $html,
            true
        );

        
        //
        // CC List
        //
        $html = '';
        $html .= $Language->getText('tracker_include_artifact','fill_cc_list_msg');
        $html .= $Language->getText('tracker_include_artifact','fill_cc_list_lbl');
        $html .= '<input type="text" name="add_cc" id="tracker_cc" size="30">';
        $html .= '<B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').":&nbsp</b>";
        $html .= '<input type="text" name="cc_comment" size="40" maxlength="255">';
        $html .= autocomplete_for_lists_users('tracker_cc', 'tracker_cc_autocomplete');
        
        echo $this->_getSection(
            'artifact_section_cc',
            $Language->getText('tracker_include_artifact','cc_list').' '. help_button('ArtifactUpdate.html#ArtifactCCList'),
            $html,
            true
        );
                
        //
        // File attachments
        //
        $html = '';
        $html .= $Language->getText('tracker_include_artifact','upload_checkbox');
        $html .= ' <input type="checkbox" name="add_file" VALUE="1">';
        $html .= $Language->getText('tracker_include_artifact','upload_file_lbl');
        $html .= '<input type="file" name="input_file" size="40">';
        $html .= $Language->getText('tracker_include_artifact','upload_file_msg',formatByteToMb($sys_max_size_attachment));

        $html .= $Language->getText('tracker_include_artifact','upload_file_desc');
        $html .= '<input type="text" name="file_description" size="60" maxlength="255">';
        
        echo $this->_getSection(
            'artifact_section_attachments',
            $Language->getText('tracker_include_artifact','attachment').' '. help_button('ArtifactUpdate.html#ArtifactAttachments'),
            $html,
            true
        );
        
        //
        // Artifact dependencies
        //
        $html = '
        <P><B>'.$Language->getText('tracker_include_artifact','dependent_on').'</B><BR>
        <P>';
        if ( !$ro ) {
        $html .= '
                        <B>'.$Language->getText('tracker_include_artifact','aids').'</B>&nbsp;
                        <input type="text" name="artifact_id_dependent" size="20" maxlength="255" value="'.$this->getID().'">
                        &nbsp;<i>'.$Language->getText('tracker_include_artifact','fill').'</i><p>';
        }
        
        echo $this->_getSection(
            'artifact_section_dependencies',
            $Language->getText('tracker_include_artifact','dependencies').' '.help_button('ArtifactUpdate.html#ArtifactDependencies'),
            $html,
            true
        );

        //
        // Final submit button
        //
        echo '<p><B><span class="highlight">'.$Language->getText('tracker_include_artifact','check_already_submitted').'</b></p>';
        echo '<div style="text-align:center"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_artifact','submit').'"></div>';
        echo '</td></tr>';
        echo '</table>';
        echo '</form>';
    }

	
        /**
         * Display the history
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         *
         * @return void
         */
        function showHistory ($group_id,$group_artifact_id) {
            //
                //      show the artifact_history rows that are relevant to this artifact_id, excluding comment (follow-up comments)
                //
            global $sys_datefmt,$art_field_fact,$sys_lf,$Language;
            $result=$this->getHistory();
            $rows=db_numrows($result);
        $html = '';
            if ($rows > 0) {
        
                        $title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','field');
                        $title_arr[]=$Language->getText('tracker_include_artifact','old_val');
                        $title_arr[]=$Language->getText('tracker_include_artifact','new_val');
                        $title_arr[]=$Language->getText('tracker_import_utils','date');
                        $title_arr[]=$Language->getText('global','by');
                
                        $html .= html_build_list_table_top ($title_arr);
                
                        for ($i=0; $i < $rows; $i++) {
                            $field_name = db_result($result, $i, 'field_name');
                            $value_id_new =  db_result($result, $i, 'new_value');
			    if (preg_match("/^(lbl_)/",$field_name) && preg_match("/(_comment)$/",$field_name) && $value_id_new == "") {
			        //removed followup comment is not recorded
				    $value_id_old = $Language->getText('tracker_include_artifact','flup_hidden');
			    } else {
			        $value_id_old =  db_result($result, $i, 'old_value');
                }
                            
                                $field = $art_field_fact->getFieldFromName($field_name);
                                if ( $field ) {
				  if ($field->userCanRead($group_id,$group_artifact_id)) {
                                    $html .= "\n".'<TR class="'. util_get_alt_row_color($i) .
                                        '"><TD>'.$field->getLabel().'</TD><TD>';
                        
                                    if ($field->isSelectBox()) {
                                                // It's a select box look for value in clear
                                                $html .= $field->getValue($group_artifact_id, $value_id_old).'</TD><TD>';
						$html .= $field->getValue($group_artifact_id, $value_id_new);
				    } else if ($field->isDateField()) {
                                                // For date fields do some special processing
                                                $html .= format_date("Y-m-j",$value_id_old).'</TD><TD>';
						
						$html .= format_date("Y-m-j",$value_id_new);

                                    } else if ($field->isFloat() ) {
                                        	$html .= number_format($value_id_old,2).'</TD><TD>';
						$html .= number_format($value_id_new,2);
                                    } else {
                                                // It's a text zone then display directly
                                                $html .= $value_id_old.'</TD><TD>';
						$html .= $value_id_new;
                                    }
                        
                                    $html .= '</TD>'.
                                        '<TD>'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
                                        '<TD>'.user_get_name_display_from_unix(db_result($result, $i, 'user_name')).'</TD></TR>';
				  }
                                } else {
				    $html .= "\n".'<TR class="'. util_get_alt_row_color($i) .
                                        '"><TD>'.((preg_match("/^(lbl_)/",$field_name) && preg_match("/(_comment)$/",$field_name)) ? "Comment" : $field_name).'</TD><TD>';
				    $html .= $value_id_old.'</TD><TD>';
				    $html .= $value_id_new;
				    $html .= '</TD>'.
                                        '<TD>'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
                                        '<TD>'.user_get_name_display_from_unix(db_result($result, $i, 'user_name')).'</TD></TR>';
				}

                        }
                $html .= '</TABLE>';
            
            } else {
                $html .= "\n".$Language->getText('tracker_include_artifact','no_changes').'</H4>';
            }
            return $html;
        }

        /**
         * Display the artifact inverse dependencies list
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showInverseDependencies ($group_id, $group_artifact_id, $ascii=false) {
        
            global $sys_datefmt,$sys_lf,$Language;
        
            //
            //      format the dependencies list for this artifact
            //
        
            $result=$this->getInverseDependencies();
            $rows=db_numrows($result);
        
            // Nobody in the dependencies list -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = $Language->getText('tracker_include_artifact','no_depend')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_include_artifact','no_depend').'</H4>';
                        return $out;
            }
        
            // Header first an determine what the print out format is
            // based on output type (Ascii, HTML)
            if ($ascii) {
		$out .= $Language->getText('tracker_include_artifact','dep_list').$sys_lf.str_repeat("*",strlen($Language->getText('tracker_include_artifact','dep_list')))."$sys_lf$sys_lf";
                        $fmt = "%-15s | %s$sys_lf";
                        $out .= sprintf($fmt, $Language->getText('tracker_include_artifact','artifact'), $Language->getText('tracker_include_artifact','summary'));
                        $out .= "------------------------------------------------------------------$sys_lf";
            } else {    
        
                        $title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','artifact');
                        $title_arr[]=$Language->getText('tracker_include_artifact','summary');
                        $title_arr[]=$Language->getText('tracker_import_admin','tracker');
                        $title_arr[]=$Language->getText('tracker_include_artifact','group');
                        $out .= html_build_list_table_top ($title_arr);
                
                        $fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td>'.
                            '<td align="center">%s</td></tr>';
                }
                
            // Loop through the denpendencies and format them
            for ($i=0; $i < $rows; $i++) {
        
                        $dependent_on_artifact_id = db_result($result, $i, 'artifact_id');
                        $summary = db_result($result, $i, 'summary');
                        $tracker_label = db_result($result, $i, 'name');
                        $group_label = db_result($result, $i, 'group_name');
                
                        if ($ascii) {
                            $out .= sprintf($fmt, $dependent_on_artifact_id, $summary);
                        } else {
                
                            $out .= sprintf($fmt,
                                            util_get_alt_row_color($i),
                                            '<a href="/tracker/?func=gotoid&group_id='. $group_id .'&aid='. $dependent_on_artifact_id .'">'. $dependent_on_artifact_id .'</a>',
                                            $summary,
                                            $tracker_label,
                                            $group_label);
                        
                        } // for
            }
        
            // final touch...
            $out .= ($ascii ? "$sys_lf" : "</TABLE>");
        
            return($out);
        
        }

        
    function displayAdd($user_id) {
        global $art_field_fact,$art_fieldset_fact,$sys_datefmt,$sys_max_size_attachment,$Language;
        
        $fields_per_line=2;
        // the column number is the number of field per line * 2 (label + value)
        // + the number of field per line -1 (a blank column between each pair "label-value" to give more space)
        $columns_number = ($fields_per_line * 2) + ($fields_per_line - 1);
        $max_size = 40;
        
        $group = $this->ArtifactType->getGroup();
        $group_artifact_id = $this->ArtifactType->getID();
        $group_id = $group->getGroupId();
        
        $result_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
        
        // Display submit informations if any
        if ( $this->ArtifactType->getSubmitInstructions() ) {
            echo util_unconvert_htmlspecialchars($this->ArtifactType->getSubmitInstructions());
        }
        
        // Beginning of the submission form with fixed fields
        echo '<FORM ACTION="'.$_SERVER['PHP_SELF'].'" METHOD="POST" enctype="multipart/form-data" NAME="artifact_form">
                <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_attachment.'">
                <INPUT TYPE="HIDDEN" NAME="func" VALUE="postadd">
                <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
                <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.$group_artifact_id.'">';
        echo '<script type="text/javascript" src="/scripts/calendar_js.php"></script>';
        echo '<TABLE><TR><TD class="artifact">';

        $html = '';
        $html .= '  <TABLE width="100%">
                <TR><TD VALIGN="TOP" COLSPAN="'.($columns_number).'">
                          <B>'.$Language->getText('tracker_include_artifact','group').':</B>&nbsp;'.group_getname($group_id).'</TD></TR>';
        
                         
                         
        // Now display the variable part of the field list (depend on the project)
        
        foreach($result_fieldsets as $fieldset_id => $result_fieldset) {
            
            // this variable will tell us if we have to display the fieldset or not (if there is at least one field to display or not)
            $display_fieldset = false;
            
            $fieldset_html = '';
            
            $i = 0;
            $fields_in_fieldset = $result_fieldset->getAllUsedFields();
            while ( list($key, $field) = each($fields_in_fieldset) ) {

                $field_html = new ArtifactFieldHtml($field);
        
                // if the field is a special field (except summary and original description)
                // or if not used by this project  then skip it. 
                // Plus only show fields allowed on the artifact submit_form 
                if ( (!$field->isSpecial() || $field->getName()=='summary' || $field->getName()=='details') ) {
                    if ($field->userCanSubmit($group_id, $group_artifact_id, $user_id)) {                                    
                        // display the artifact field with its default value
                        // if field size is greatest than max_size chars then force it to
                        // appear alone on a new line or it won't fit in the page
    
                        // if the user can submit at least one field, we can display the fieldset this field is within
                        $display_fieldset = true;
                        
                        $field_value = $field->getDefaultValue();
                        list($sz,) = $field->getGlobalDisplaySize();
                        $label = $field_html->labelDisplay(false,false,true);
                        $value = $field_html->display($group_artifact_id,$field_value,false,false);
                        $star = ($field->isEmptyOk() ? '':'<span class="highlight"><big>*</big></b></span>');
    
                        if ( ($sz > $max_size) || ($field->getName()=='details') ) {
                            $fieldset_html .= "\n<TR>".
                                '<TD valign="top"><a class="tooltip" href="#" title="'.$field->getDescription().'">'.$label.$star.'</a></td>'.
                                '<TD valign="middle" colspan="'.($columns_number-1).'">'.
                                $value.'</TD>'.                   
                                "\n</TR>";
                            $i=0;
                        } else {
                            $fieldset_html .= ($i % $fields_per_line ? '':"\n<TR>");
                            $fieldset_html .= '<TD valign="middle"><a class="tooltip" href="#" title="'.$field->getDescription().'">'.$label.$star.'</a></td>'.
                                  '<TD valign="middle">'.$value.'</TD>';
                            $i++;
                            $fieldset_html .= ($i % $fields_per_line ? '<td class="artifact_spacer">&nbsp;</td>':"\n</TR>");
                        }
                    }
                }
            } // while
            
            // We display the fieldset only if there is at least one field inside that we can display
            if ($display_fieldset) {
                $html .= '<TR><TD COLSPAN="'.$columns_number.'">&nbsp</TD></TR>';
                $html .= '<TR class="boxtitle"><TD class="left" COLSPAN="'.$columns_number.'">&nbsp;<span title="'.$result_fieldset->getDescriptionText().'">'.$result_fieldset->getLabel().'</span></TD></TR>';
                $html .= $fieldset_html;
            }
            
        }
            
        $html .= '</TABLE>';
        
        echo $this->_getSection(
            'artifact_section_details',
            'Details',
            $html,
            true
        );
        
        //
        // CC List
        //
        $html = '';
        $html .= $Language->getText('tracker_include_artifact','fill_cc_list_msg');
        $html .= $Language->getText('tracker_include_artifact','fill_cc_list_lbl');
        $html .= '<input type="text" name="add_cc" id="tracker_cc" size="30">';
        $html .= '<B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').":&nbsp</b>";
        $html .= '<input type="text" name="cc_comment" size="40" maxlength="255">';
        $html .= autocomplete_for_lists_users('tracker_cc', 'tracker_cc_autocomplete');
        
        echo $this->_getSection(
            'artifact_section_cc',
            $Language->getText('tracker_include_artifact','cc_list').' '. help_button('ArtifactUpdate.html#ArtifactCCList'),
            $html,
            true
        );
                
        //
        // File attachments
        //
        $html = '';
        $html .= $Language->getText('tracker_include_artifact','upload_checkbox');
        $html .= ' <input type="checkbox" name="add_file" VALUE="1">';
        $html .= $Language->getText('tracker_include_artifact','upload_file_lbl');
        $html .= '<input type="file" name="input_file" size="40">';
        $html .= $Language->getText('tracker_include_artifact','upload_file_msg',formatByteToMb($sys_max_size_attachment));

        $html .= $Language->getText('tracker_include_artifact','upload_file_desc');
        $html .= '<input type="text" name="file_description" size="60" maxlength="255">';
        
        echo $this->_getSection(
            'artifact_section_attachments',
            $Language->getText('tracker_include_artifact','attachment').' '. help_button('ArtifactUpdate.html#ArtifactAttachments'),
            $html,
            true
        );
        
        //
        // Final submit button
        //
        echo '<p><B><span class="highlight">'.$Language->getText('tracker_include_artifact','check_already_submitted').'</b></p>';
        echo '<div style="text-align:center"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_artifact','submit').'"></div>';
        echo '</td></tr>';
        echo '</table>';
        echo '</form>';
    }

    /**
         * Display the follow-up comment update form	 
         *
         * @param comment_id: id of the follow-up comment
         * 
         *
         * @return void
         */
    function displayEditFollowupComment($comment_id) {
    	 
    	$group = $this->ArtifactType->getGroup();
    	$group_artifact_id = $this->ArtifactType->getID();
    	$group_id = $group->getGroupId();
    	echo '<H2>'.$GLOBALS['Language']->getText('tracker_edit_comment','upd_followup').'</H2>';
    	echo '<FORM ACTION="/tracker/?group_id='.$group_id.'&atid='.$group_artifact_id.'&func=browse" METHOD="post">
		<INPUT TYPE="hidden" NAME="artifact_history_id" VALUE="'.$comment_id.'">
		<INPUT TYPE="hidden" NAME="artifact_id" VALUE="'.$this->getID().'">
		<P><TEXTAREA NAME="followup_update" ROWS="7" COLS="80" WRAP="SOFT">'.$this->getFollowup($comment_id).'</TEXTAREA>
		<P><INPUT TYPE="submit" VALUE="Submit">
		</FORM>';

    }
    
}

?>
