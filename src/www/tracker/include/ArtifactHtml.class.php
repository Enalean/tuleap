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
            
            
            // First display some  internal fields - Cannot be modified by the user          
            $summary = $this->getValue('summary');
            
            echo "\n<TABLE width='100%'><TR><TD>";
            echo "\n<H2>[ ".$this->ArtifactType->getItemName();
            $field_artifact_id = $result_fields['artifact_id'];
            if ($field_artifact_id->userCanRead($group_id, $group_artifact_id, $user_id)) {
                echo " #".$this->getID();
            }
            echo " ] ".$summary."</H2>";
            echo "</TD>";
            
            if ( $pv == 0) {
                echo "<TD align='right'><A HREF='?func=detail&aid=".$this->getID()."&group_id=".$group_id."&atid=".$group_artifact_id."&pv=1' target='_blank'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A></TD></TR>";
            }

            if ($this->ArtifactType->allowsCopy()) {
              echo "\n<TR><TD><A HREF='?func=copy&aid=".$this->getID()."&group_id=".$group_id."&atid=".$group_artifact_id."'>".$Language->getText('tracker_include_artifact','copy_art')."</A></TD></TR></TABLE><br><br>";
            } else {
    
                echo "</TABLE>";
            }
        
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
            
            $html  = '<TABLE><TR>';
            $html .= '<TD align="left"><B>'.$Language->getText('tracker_include_artifact','project').'</B>&nbsp;</td><td COLSPAN="'.($columns_number-1).'">'.group_getname($group_id).'</TD>';
            echo $html;
            
            echo '<script type="text/javascript" src="/scripts/calendar_js.php"></script>';
            
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
                                  '<TD align="left" valign="top">'.$field_html['label'].'</td>'.
                                  '<TD valign="top" colspan="'.($columns_number-1).'">'.$field_html['value'].'</TD>'.                     
                                  "\n</TR>";
                                $i=0;
                            } else {
                                $fieldset_html .= ($i % $fields_per_line ? '':"\n<TR>");
                                $fieldset_html .= '<TD align="left" valign="top">'.$field_html['label'].'</td>'.
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
                    echo '<TR><TD COLSPAN="'.$columns_number.'">&nbsp</TD></TR>';
                    echo '<TR class="boxtitle"><TD class="left" COLSPAN="'.$columns_number.'">&nbsp;<span title="'.$result_fieldset->getDescriptionText().'">'.$result_fieldset->getLabel().'</span></TD></TR>';
                    echo $fieldset_html;
                }

            }
            
            echo '<tr><td><p><font color="red">*</font>: '.
                 $Language->getText('tracker_include_type','fields_requ').
                 '</p></td></tr></TABLE>';
            
            echo '<table cellspacing="0">';

            //
            // Followups comments
            //
            echo '<TR><TD colspan="2" align="top"><HR></td></TR>';
            if ( !$ro ) {
                    echo '
                    <TR><TD>
                    <h3>'.$Language->getText('tracker_include_artifact','follow_ups').' '.help_button('ArtifactUpdate.html#ArtifactComments').'</h3></td>
                    <TD>
                    <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_artifact','submit').'">
                    </td></tr>';

                    echo '
                    <tr><TD colspan="2" align="top">
                    <B>'.$Language->getText('tracker_include_artifact','use_canned').'</B>&nbsp;';
                    
                    echo $this->ArtifactType->cannedResponseBox ();
                    
                    echo '
                    &nbsp;&nbsp;&nbsp;<A HREF="/tracker/admin/?func=canned&atid='.$group_artifact_id.'&group_id='.$group_id.'&create_canned=1">'.$Language->getText('tracker_include_artifact','define_canned').'</A>
                    </TD></TR>';
                    
                    echo '
                    <TR><TD colspan="2">';
                    
                    $field = $art_field_fact->getFieldFromName('comment_type_id');
                    if ( $field && $field->isUsed()) {
                            $field_html = new ArtifactFieldHtml( $field );
                            echo '<P><B>'.$Language->getText('tracker_include_artifact','comment_type').'</B>'.
                                 $field_html->fieldBox('',$group_artifact_id,$field->getDefaultValue(),true,$Language->getText('global','none')).'<BR>';
                    }
                    echo '<TEXTAREA NAME="comment" ROWS="7" COLS="80" WRAP="SOFT"></TEXTAREA><p>';
            } else {
                    if ($pv == 0) {
                            echo '<br><TR><TD COLSPAN="2"><B>'.$Language->getText('tracker_include_artifact','add_comment').'</B><BR>
                        <TEXTAREA NAME="comment" ROWS="7" COLS="60" WRAP="SOFT"></TEXTAREA><p>';
                    }
            }
                            
            if (!user_isloggedin() && ($pv == 0)) {
                    echo $Language->getText('tracker_include_artifact','not_logged_in','/account/login.php?return_to='.urlencode($_SERVER['REQUEST_URI']));
            echo '<br><input type="text" name="email" maxsize="100" size="50"/><p>';
            }
    
            echo $this->showFollowUpComments($group_id);
            echo '</td></tr>';
            
            //
            // CC List
            //
            echo '          
            <TR><TD colspan="2"><hr></td></tr>
            
            <TR><TD colspan="2">
            <h3>'.$Language->getText('tracker_include_artifact','cc_list').' '.help_button('ArtifactUpdate.html#ArtifactCCList').'</h3>';
            
            if ($pv == 0) {
                echo $Language->getText('tracker_include_artifact','fill_cc_list_msg');
                echo $Language->getText('tracker_include_artifact','fill_cc_list_lbl');
                echo '<input type="text" name="add_cc" id="tracker_cc" size="30">';
                echo '<B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').":&nbsp</b>";
                echo '<input type="text" name="cc_comment" size="40" maxlength="255">';
                autocomplete_for_lists_users('tracker_cc', 'tracker_cc_autocomplete');
            }
                    
            echo $this->showCCList($group_id,$group_artifact_id);
                    
            echo '</TD></TR>';
                    
            //
            // File attachments
            //
            echo '
            <TR><TD colspan="2"><hr></td></tr>
            <TR><TD colspan="2">
            <h3>'.$Language->getText('tracker_include_artifact','attachment').' '.help_button('ArtifactUpdate.html#ArtifactAttachments').'</h3>';
            
            if ($pv == 0) {
                echo $Language->getText('tracker_include_artifact','upload_checkbox');
                echo ' <input type="checkbox" name="add_file" VALUE="1">';
                echo $Language->getText('tracker_include_artifact','upload_file_lbl');
                echo '<input type="file" name="input_file" size="40">';
                echo $Language->getText('tracker_include_artifact','upload_file_msg',formatByteToMb($sys_max_size_attachment));

                echo $Language->getText('tracker_include_artifact','upload_file_desc');
                echo '<input type="text" name="file_description" size="60" maxlength="255">';
            }
            echo $this->showAttachedFiles($group_id,$group_artifact_id);
            
            echo '</TD></TR>';

            //
            // Artifact dependencies
            //
            echo '
            <TR><TD colspan="2"><hr></td></tr>
            <TR ><TD colspan="2">';
            
            echo '<h3>'.$Language->getText('tracker_include_artifact','dependencies').' '.help_button('ArtifactUpdate.html#ArtifactDependencies').'</h3>
            <B>'.$Language->getText('tracker_include_artifact','depend_on').'</B><BR>
            <P>';
            if ( !$ro ) {
                    echo '
                    <B>'.$Language->getText('tracker_include_artifact','aids').'</B>&nbsp;
                    <input type="text" name="artifact_id_dependent" size="20" maxlength="255">
                    &nbsp;<i>'.$Language->getText('tracker_include_artifact','fill').'</i><p>';
            }
            echo $this->showDependencies($group_id,$group_artifact_id);
            
            echo '
            <P><B>'.$Language->getText('tracker_include_artifact','dependent_on').'</B><BR>
            <P>';
            echo $this->showInverseDependencies($group_id,$group_artifact_id);
            
            echo '</TD></TR>';
            echo '<TR><TD colspan="2"><hr></td></tr>';
        
            //
            // History
            //
            echo '
                <TR><TD colspan="2" >';
                
            echo '<H3>'.$Language->getText('tracker_include_artifact','change_history').' '.help_button('ArtifactUpdate.html#ArtifactHistory').'</H3>';
            echo $this->showHistory($group_id,$group_artifact_id);
            
            // 
            // Final submit button
            //
            if ( $pv == 0) {
                    echo '</TD></TR>                
                    <TR><TD colspan="2" ALIGN="center">
                            <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_artifact','submit').'">
                            </FORM>
                    </TD></TR>';
            }
            
            echo '</table>';
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
        
        $summary = $this->getValue('summary');
          
        echo "<TABLE width='100%'><TR><TD>";
        echo "<H2>[ ".$Language->getText('tracker_include_artifact','copy_of',$this->ArtifactType->getItemName()." #".$this->getID())." ] ".$summary."</H2>";
        echo "</TD></TR></TABLE>";
          
        
        echo '
            <table>
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
                echo '<TR><TD COLSPAN="'.$columns_number.'">&nbsp</TD></TR>';
                echo '<TR class="boxtitle"><TD class="left" COLSPAN="'.$columns_number.'">&nbsp;<span title="'.$result_fieldset->getDescriptionText().'">'.$result_fieldset->getLabel().'</span></TD></TR>';
                echo $fieldset_html;
            }
            
        }
        
        echo '</TABLE>';
          
        echo '<table cellspacing="0">';
        
        //
        // Followups comments
        //
        echo '<TR><TD colspan="2" align="top"><HR></td></TR>';
        if ( !$ro ) {
        echo '
                        <TR><TD>
                        <h3>'.$Language->getText('tracker_include_artifact','follow_ups').' '.help_button('ArtifactUpdate.html#ArtifactComments').'</h3></td>
                        <TD>
                        <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_artifact','submit').'">
                        </td></tr>';
        
        echo '
                        <tr><TD colspan="2" align="top">
                        <B>'.$Language->getText('tracker_include_artifact','use_canned').'</B>&nbsp;';
        
        echo $this->ArtifactType->cannedResponseBox ();
            
        echo '
                        &nbsp;&nbsp;&nbsp;<A HREF="/tracker/admin/?func=canned&atid='.$group_artifact_id.'&group_id='.$group_id.'&create_canned=1">'.$Language->getText('tracker_include_artifact','define_canned').'</A>
                        </TD></TR>';
        
        echo '
                        <TR><TD colspan="2">';
        
        $field = $art_field_fact->getFieldFromName('comment_type_id');
        if ( $field && $field->isUsed()) {
          $field_html = new ArtifactFieldHtml( $field );
          echo '<P><B>'.$Language->getText('tracker_include_artifact','comment_type').'</B>'.
        $field_html->fieldBox('',$group_artifact_id,$field->getDefaultValue(),true,$Language->getText('global','none')).'<BR>';
        }
        echo '<TEXTAREA NAME="follow_up_comment" ROWS="7" COLS="60" WRAP="SOFT">'.$Language->getText('tracker_include_artifact','is_copy',array($this->ArtifactType->getItemName(),$this->ArtifactType->getItemName().' #'.$this->getID())).'</TEXTAREA><p>';
        } else {
        if ($pv == 0) {
          echo '<br><TR><TD COLSPAN="2"><B>'.$Language->getText('tracker_include_artifact','add_comment').'</B><BR>
                            <TEXTAREA NAME="follow_up_comment" ROWS="7" COLS="60" WRAP="SOFT"></TEXTAREA><p>';
        }
        }
          
        echo '</td></tr>';
          
        //
        // CC List
        //
        echo '          
                <TR><TD colspan="2"><hr></td></tr>
                
                <TR><TD colspan="2">
                <h3>'.$Language->getText('tracker_include_artifact','cc_list').' '.help_button('ArtifactUpdate.html#ArtifactCCList').'</h3>';
        
        if ( !$ro ) {
        echo $Language->getText('tracker_include_artifact','fill_cc_list_msg');
        echo $Language->getText('tracker_include_artifact','fill_cc_list_lbl');
        echo '<input type="text" name="add_cc" id="tracker_cc" size="30">';
        echo '<B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').":&nbsp</b>";
        echo '<input type="text" name="cc_comment" size="40" maxlength="255">';
        autocomplete_for_lists_users('tracker_cc', 'tracker_cc_autocomplete');
        }
          
        echo '</TD></TR>';
          
        //
        // File attachments
        //
        echo '
                <TR><TD colspan="2"><hr></td></tr>
                <TR><TD colspan="2">
                <h3>'.$Language->getText('tracker_include_artifact','attachment').' '.help_button('ArtifactUpdate.html#ArtifactAttachments').'</h3>';
        
        
        echo $Language->getText('tracker_include_artifact','upload_checkbox');
        echo ' <input type="checkbox" name="add_file" VALUE="1">';
        echo $Language->getText('tracker_include_artifact','upload_file_lbl');
        echo '<input type="file" name="input_file" size="40">';
        echo $Language->getText('tracker_include_artifact','upload_file_msg',formatByteToMb($sys_max_size_attachment));
        
        echo $Language->getText('tracker_include_artifact','upload_file_desc');
        echo '<input type="text" name="file_description" size="60" maxlength="255">';
          
        echo '</TD></TR>';
        
        //
        // Artifact dependencies
        //
        echo '
                <TR><TD colspan="2"><hr></td></tr>
                <TR ><TD colspan="2">';
        
        echo '<h3>'.$Language->getText('tracker_include_artifact','dependencies').' '.help_button('ArtifactUpdate.html#ArtifactDependencies').'</h3>
                <B>'.$Language->getText('tracker_include_artifact','depend_on').'</B><BR>
                <P>';
        if ( !$ro ) {
        echo '
                        <B>'.$Language->getText('tracker_include_artifact','aids').'</B>&nbsp;
                        <input type="text" name="artifact_id_dependent" size="20" maxlength="255" value="'.$this->getID().'">
                        &nbsp;<i>'.$Language->getText('tracker_include_artifact','fill').'</i><p>
                  </TD></TR>';
        
        }
          
          //
        // Final submit button
        //
        echo '
                <TR><TD COLSPAN="'.(2*$fields_per_line).'">
                        <P>
                        <hr>
                        <B><span class="highlight">'.$Language->getText('tracker_include_artifact','check_already_submitted').'<P><center>
                        <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
                        </center>
                        </FORM>
                </TD></TR>
                </TABLE>';
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
        
            if ($rows > 0) {
        
                        $title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','field');
                        $title_arr[]=$Language->getText('tracker_include_artifact','old_val');
                        $title_arr[]=$Language->getText('tracker_include_artifact','new_val');
                        $title_arr[]=$Language->getText('tracker_import_utils','date');
                        $title_arr[]=$Language->getText('global','by');
                
                        echo html_build_list_table_top ($title_arr);
                
                        for ($i=0; $i < $rows; $i++) {
                            $field_name = db_result($result, $i, 'field_name');
                            $value_id_old =  db_result($result, $i, 'old_value');
                            $value_id_new =  db_result($result, $i, 'new_value');

                                $field = $art_field_fact->getFieldFromName($field_name);
                                if ( $field ) {
				  if ($field->userCanRead($group_id,$group_artifact_id)) {
                                    echo "\n".'<TR class="'. util_get_alt_row_color($i) .
                                        '"><TD>'.$field->getLabel().'</TD><TD>';
                        
                                    if ($field->isSelectBox()) {
                                                // It's a select box look for value in clear
                                                echo $field->getValue($group_artifact_id, $value_id_old).'</TD><TD>';
						echo $field->getValue($group_artifact_id, $value_id_new);
				    } else if ($field->isDateField()) {
                                                // For date fields do some special processing
                                                echo format_date("Y-m-j",$value_id_old).'</TD><TD>';
						
						echo format_date("Y-m-j",$value_id_new);

                                    } else if ($field->isFloat() ) {
                                        	echo number_format($value_id_old,2).'</TD><TD>';
						echo number_format($value_id_new,2);
                                    } else {
                                                // It's a text zone then display directly
                                                echo $value_id_old.'</TD><TD>';
						echo $value_id_new;
                                    }
                        
                                    echo '</TD>'.
                                        '<TD>'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
                                        '<TD>'.db_result($result, $i, 'user_name').'</TD></TR>';
				  }
                                } else {
				    echo "\n".'<TR class="'. util_get_alt_row_color($i) .
                                        '"><TD>'.$field_name.'</TD><TD>';
				    echo $value_id_old.'</TD><TD>';
				    echo $value_id_new;
				    echo '</TD>'.
                                        '<TD>'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
                                        '<TD>'.db_result($result, $i, 'user_name').'</TD></TR>';
				}

                        }
                echo '</TABLE>';
            
            } else {
                echo "\n".$Language->getText('tracker_include_artifact','no_changes').'</H4>';
            }
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
                                            "<a href=\"/tracker/?func=gotoid&group_id=$group_id&aid=$dependent_on_artifact_id\">$dependent_on_artifact_id</a>",
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
        echo '  <TABLE>
                <TR><TD VALIGN="TOP" COLSPAN="'.($columns_number).'">
                          <B>'.$Language->getText('tracker_include_artifact','group').':</B>&nbsp;'.group_getname($group_id).'</TD></TR>';
        echo '<script type="text/javascript" src="/scripts/calendar_js.php"></script>';
        
                         
                         
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
                echo '<TR><TD COLSPAN="'.$columns_number.'">&nbsp</TD></TR>';
                echo '<TR class="boxtitle"><TD class="left" COLSPAN="'.$columns_number.'">&nbsp;<span title="'.$result_fieldset->getDescriptionText().'">'.$result_fieldset->getLabel().'</span></TD></TR>';
                echo $fieldset_html;
            }
            
        }
            
        echo '</TABLE>';
          
        echo '<table cellspacing="0">';
                         
    
                     
        // Then display all mandatory fields 
        
        //
        // CC List
        //
        echo '          
        <TR><TD colspan="'.(2*$fields_per_line).'"><hr></td></tr>
        
        <TR><TD colspan="'.(2*$fields_per_line).'">
        <h3>'.$Language->getText('tracker_include_artifact','cc_list').' '.help_button('ArtifactUpdate.html#ArtifactCCList').'</h3>
        '.$Language->getText('tracker_include_artifact','fill_cc_list_msg').'<p>
        <B>'.$Language->getText('tracker_include_artifact','fill_cc_list_lbl').'&nbsp;</b><input type="text" name="add_cc" id="tracker_cc" size="30">&nbsp;&nbsp;&nbsp;
        <B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').':&nbsp;</b><input type="text" name="cc_comment" size="40" maxlength="255"><p>';
        autocomplete_for_lists_users('tracker_cc', 'tracker_cc_autocomplete');
        
        echo '</TD></TR>';
                
        //
        // File attachments
        //
        echo '
        <TR><TD colspan="'.(2*$fields_per_line).'"><hr></td></tr>
        <TR><TD colspan="'.(2*$fields_per_line).'">
        <h3>'.$Language->getText('tracker_include_artifact','attachment').' '.help_button('ArtifactUpdate.html#ArtifactAttachments').'</h3>';
        
        
        echo $Language->getText('tracker_include_artifact','upload_checkbox');
        echo ' <input type="checkbox" name="add_file" VALUE="1">';
        echo $Language->getText('tracker_include_artifact','upload_file_lbl');
        echo '<input type="file" name="input_file" size="40">';
        echo $Language->getText('tracker_include_artifact','upload_file_msg',formatByteToMb($sys_max_size_attachment));
        
        echo $Language->getText('tracker_include_artifact','upload_file_desc');
        echo '<input type="text" name="file_description" size="60" maxlength="255">';                
        echo '</TD></TR>';
        
        //
        // Final submit button
        //
        echo '
        <TR><TD COLSPAN="'.(2*$fields_per_line).'">
                <P>
                <hr>
                <B><span class="highlight">'.$Language->getText('tracker_include_artifact','check_already_submitted').'<P><center>
                <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
                </center>
                </FORM>
        </TD></TR>
        </TABLE>';
    }


}

?>
