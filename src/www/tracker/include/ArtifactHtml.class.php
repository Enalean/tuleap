<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
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
                echo '<input type="text" name="add_cc" size="30">';
                echo '<B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').":&nbsp</b>";
                echo '<input type="text" name="cc_comment" size="40" maxlength="255">';
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
        echo '<input type="text" name="add_cc" size="30">';
        echo '<B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').":&nbsp</b>";
        echo '<input type="text" name="cc_comment" size="40" maxlength="255">';
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
                                        '<TD>'.user_get_name_display_from_unix(db_result($result, $i, 'user_name')).'</TD></TR>';
				}

                        }
                echo '</TABLE>';
            
            } else {
                echo "\n".$Language->getText('tracker_include_artifact','no_changes').'</H4>';
            }
        }


        /**
         * Display the list of attached files
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showAttachedFiles ($group_id,$group_artifact_id,$ascii=false) {
        
            global $sys_datefmt,$sys_lf,$Language;
        
            //
            //  show the files attached to this artifact
            //   
        
            $result=$this->getAttachedFiles();
            $rows=db_numrows($result);
        
            // No file attached -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = $Language->getText('tracker_include_artifact','no_file_attached')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_include_artifact','no_file_attached').'</H4>';
                        return $out;
                }
                
            // Header first
            if ($ascii) {
		$out = $Language->getText('tracker_include_artifact','file_attachment').$sys_lf.str_repeat("*",strlen($Language->getText('tracker_include_artifact','file_attachment')));
            } else {    
                
                $title_arr=array();
                $title_arr[]=$Language->getText('tracker_include_artifact','name');
                $title_arr[]=$Language->getText('tracker_include_artifact','desc');
                $title_arr[]=$Language->getText('tracker_include_artifact','size_kb');
                $title_arr[]=$Language->getText('global','by');
                $title_arr[]=$Language->getText('tracker_include_artifact','posted_on');
		$title_arr[]=$Language->getText('tracker_include_canned','delete');
        
                $out = html_build_list_table_top ($title_arr);
            }
        
            // Determine what the print out format is based on output type (Ascii, HTML)
            if ($ascii) {
                        $fmt = "$sys_lf$sys_lf------------------------------------------------------------------$sys_lf".
                            $Language->getText('tracker_import_utils','date').": %s  ".$Language->getText('tracker_include_artifact','name').": %s  ".$Language->getText('tracker_include_artifact','size').": %dKB   ".$Language->getText('global','by').": %s$sys_lf%s$sys_lf%s";
            } else {
                        $fmt = "$sys_lf".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td>'.
                    '<td align="center">%s</td></tr>';
            }
        
            // Determine which protocl to use for embedded URL in ASCII format
            $server=get_server_url();
        
            // Loop throuh the attached files and format them
            for ($i=0; $i < $rows; $i++) {
        
                        $artifact_file_id = db_result($result, $i, 'id');
                        $href = "/tracker/download.php?artifact_id=".$this->getID()."&id=".$artifact_file_id;
                
                        if ($ascii) {
                            $out .= sprintf($fmt,
                                            format_date($sys_datefmt,db_result($result, $i, 'adddate')),
                                            db_result($result, $i, 'filename'),
                                            intval(db_result($result, $i, 'filesize')/1024),
                                            db_result($result, $i, 'user_name'),
                                            db_result($result, $i, 'description'),
                                            $server.$href);
                        } else {
                            // show CC delete icon if one of the condition is met:
                            // (a) current user is group member
                            // (b) the current user is the person who added a gieven name in CC list
			  if ( user_ismember($this->ArtifactType->getGroupID()) ||
                                (user_getname(user_getid()) == db_result($result, $i, 'user_name') )) {
                                        $html_delete = "<a href=\"".$_SERVER['PHP_SELF']."?func=delete_file&group_id=".$group_id."&atid=".$group_artifact_id."&aid=".$this->getID()."&id=".db_result($result, $i, 'id')."\" ".
                                            " onClick=\"return confirm('".$Language->getText('tracker_include_artifact','delete_attachment')."')\">".
                                            '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('global','btn_delete').'"></A>';
                            } else {
                                        $html_delete = '-';
                            }
                            $out .= sprintf($fmt,
                                            util_get_alt_row_color($i),
                                            "<a href=\"$href\">". db_result($result, $i, 'filename').'</a>',
                                            db_result($result, $i, 'description'),
                                            intval(db_result($result, $i, 'filesize')/1024),
                                            util_user_link(db_result($result, $i, 'user_name')),
                                            format_date($sys_datefmt,db_result($result, $i, 'adddate')),
                                            $html_delete);
                        }
        } // for
        
            // final touch...
            $out .= ($ascii ? "$sys_lf" : "</TABLE>");
        
            return($out);
        
        }

        /**
         * Display the list of CC addresses
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showCCList ($group_id, $group_artifact_id, $ascii=false) {
        
            global $sys_datefmt,$sys_lf,$Language;
        
            //
            //      format the CC list for this artifact
            //
        
            $result = $this->getCCList();
            $rows   = db_numrows($result);
            $out    = '';
            
            // Nobody in the CC list -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = $Language->getText('tracker_include_artifact','cc_empty')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_include_artifact','cc_empty').'</H4>';
                        return $out;
            }
        
            // Header first an determine what the print out format is
            // based on output type (Ascii, HTML)
            if ($ascii) {
		$out .= $Language->getText('tracker_include_artifact','cc_list').$sys_lf.str_repeat("*",strlen($Language->getText('tracker_include_artifact','cc_list'))).$sys_lf.$sys_lf;
                        $fmt = "%-35s | %s$sys_lf";
                        $out .= sprintf($fmt, $Language->getText('tracker_include_artifact','cc_address'), $Language->getText('tracker_include_artifact','fill_cc_list_cmt'));
                        $out .= "------------------------------------------------------------------$sys_lf";
            } else {    
        
                        $title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','cc_address');
                        $title_arr[]=$Language->getText('tracker_include_artifact','fill_cc_list_cmt');
                        $title_arr[]=$Language->getText('tracker_include_artifact','added_by');
                        $title_arr[]=$Language->getText('tracker_include_artifact','posted_on');
                        $title_arr[]=$Language->getText('tracker_include_canned','delete');
                        $out .= html_build_list_table_top ($title_arr);
                
                        $fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td>'.
                            '<td align="center">%s</td><td align="center">%s</td></tr>';
                }
                
            // Loop through the cc and format them
            for ($i=0; $i < $rows; $i++) {
        
                        $email = db_result($result, $i, 'email');
                        $artifact_cc_id = db_result($result, $i, 'artifact_cc_id');
                
                        // if the CC is a user point to its user page else build a mailto: URL
                        $res_username = user_get_result_set_from_unix($email);
                        if ($res_username && (db_numrows($res_username) == 1))
                            $href_cc = util_user_link($email);
                        else
                            $href_cc = "<a href=\"mailto:".util_normalize_email($email)."\">".$email.'</a>';
                
                        if ($ascii) {
                            $out .= sprintf($fmt, $email, db_result($result, $i, 'comment'));
                        } else {
                
                            // show CC delete icon if one of the condition is met:
                            // (a) current user is a group member
                            // (b) the CC name is the current user 
                            // (c) the CC email address matches the one of the current user
                            // (d) the current user is the person who added a gieven name in CC list
                            if ( user_ismember($this->ArtifactType->getGroupID()) ||
                                (user_getname(user_getid()) == $email) ||  
                                (user_getemail(user_getid()) == $email) ||
                                (user_getname(user_getid()) == db_result($result, $i, 'user_name') )) {
                                        $html_delete = "<a href=\"".$_SERVER['PHP_SELF']."?func=delete_cc&group_id=$group_id&aid=".$this->getID()."&atid=".$group_artifact_id."&artifact_cc_id=$artifact_cc_id\" ".
                                        " onClick=\"return confirm('".$Language->getText('tracker_include_artifact','delete_cc')."')\">".
                                        '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('global','btn_delete').'"></A>';
                            } else {
                                        $html_delete = '-';
                            }
                
                            $out .= sprintf($fmt,
                                            util_get_alt_row_color($i),
                                            $href_cc,
                                            db_result($result, $i, 'comment'),
                                            util_user_link(db_result($result, $i, 'user_name')),
                                            format_date($sys_datefmt,db_result($result, $i, 'date')),
                                            $html_delete);
                        
                        } // for
            }
        
            // final touch...
            $out .= ($ascii ? "$sys_lf" : "</TABLE>");
        
            return($out);
        
        }

        /**
         * Display the artifact dependencies list
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showDependencies ($group_id, $group_artifact_id, $ascii=false) {
        
            global $sys_datefmt,$sys_lf,$Language;
        
            //
            //      format the dependencies list for this artifact
            //
        
            $result=$this->getDependencies();
            $rows=db_numrows($result);
        
            // Nobody in the dependencies list -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = $Language->getText('tracker_include_artifact','dep_list_empty')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_include_artifact','dep_list_empty').'</H4>';
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
                        $title_arr[]=$Language->getText('tracker_include_canned','delete');
                        $out .= html_build_list_table_top ($title_arr);
                
                        $fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td>'.
                            '<td align="center">%s</td><td align="center">%s</td></tr>';
                }
                
            // Loop through the denpendencies and format them
            for ($i=0; $i < $rows; $i++) {
        
                        $dependent_on_artifact_id = db_result($result, $i, 'is_dependent_on_artifact_id');
                        $summary = db_result($result, $i, 'summary');
                        $tracker_label = db_result($result, $i, 'name');
                        $group_label = db_result($result, $i, 'group_name');
                
                        if ($ascii) {
                            $out .= sprintf($fmt, $dependent_on_artifact_id, $summary);
                        } else {
                
                            if ( user_ismember($this->ArtifactType->getGroupID()) ) {
                                        $html_delete = "<a href=\"".$_SERVER['PHP_SELF']."?func=delete_dependent&group_id=$group_id&aid=".$this->getID()."&atid=".$group_artifact_id."&dependent_on_artifact_id=$dependent_on_artifact_id\" ".
                                        " onClick=\"return confirm('".$Language->getText('tracker_include_artifact','del_dep')."')\">".
                                        '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('global','btn_delete').'"></A>';
                            } else {
                                        $html_delete = '-';
                            }
                
                            $out .= sprintf($fmt,
                                            util_get_alt_row_color($i),
                                            "<a href=\"/tracker/?func=gotoid&group_id=$group_id&aid=$dependent_on_artifact_id\">$dependent_on_artifact_id</a>",
                                            $summary,
                                            $tracker_label,
                                            $group_label,
                                            $html_delete);
                        
                        } // for
            }
        
            // final touch...
            $out .= ($ascii ? "$sys_lf" : "</TABLE>");
        
            return($out);
        
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

        /**
         * Display the follow ups comments
         *
         * @param group_id: the group id
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showFollowUpComments($group_id, $ascii=false) {

            //
            //  Format the comment rows from artifact_history
            //  
            global $sys_datefmt,$sys_lf,$Language;
            
                $group = $this->ArtifactType->getGroup();
                $group_artifact_id = $this->ArtifactType->getID();
                $group_id = $group->getGroupId();

            $result=$this->getFollowups ();
            $rows=db_numrows($result);
        
            // No followup comment -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = "$sys_lf$sys_lf ".$Language->getText('tracker_import_utils','no_followups')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_import_utils','no_followups').'</H4>';
                        return $out;
            }
        
            $out = '';
            
            // Header first
            if ($ascii) {
		$out .= $Language->getText('tracker_include_artifact','follow_ups').$sys_lf.str_repeat("*",strlen($Language->getText('tracker_include_artifact','follow_ups')));
            } else {
                        $title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','fill_cc_list_cmt');
                        $title_arr[]=$Language->getText('tracker_import_utils','date');
                        $title_arr[]=$Language->getText('global','by');
                        
                        $out .= html_build_list_table_top ($title_arr);
            }
            
            // Loop throuh the follow-up comments and format them
            for ($i=0; $i < $rows; $i++) {
                        $comment_type = db_result($result, $i, 'comment_type');
			$comment_type_id = db_result($result, $i, 'comment_type_id');
		        if ( ($comment_type_id == 100) ||($comment_type == "") )
                            $comment_type = '';
                        else
                            $comment_type = '['.$comment_type.']';
                        if ($ascii) {
                            $fmt = "$sys_lf$sys_lf------------------------------------------------------------------$sys_lf".
                                $Language->getText('tracker_import_utils','date').": %-30s".$Language->getText('global','by').": %s$sys_lf".
                                ($comment_type != ""? "%s$sys_lf%s" : '%s');
                        } else {
                            $fmt = "\n".'<tr class="%s"><td>'.($comment_type != ""? "<b>%s</b><BR>" : "").'%s</td>'.
                                '<td valign="top">%s</td><td valign="top">%s</td></tr>';
                        }
                
                        // I wish we had sprintf argument swapping in PHP3 but
                        // we don't so do it the ugly way...
                        if ($ascii) {
                                if ( $comment_type != "" ) {
                                    $out .= sprintf($fmt,
                                                    format_date($sys_datefmt,db_result($result, $i, 'date')),
                                                    (db_result($result, $i, 'mod_by')==100?db_result($result, $i, 'email'):user_get_name_display_from_unix(db_result($result, $i, 'user_name'))),
                                                    $comment_type,
                                                    util_unconvert_htmlspecialchars(db_result($result, $i, 'old_value'))
                                                    );
                                } else {
                                    $out .= sprintf($fmt,
                                                    format_date($sys_datefmt,db_result($result, $i, 'date')),
                                                    (db_result($result, $i, 'mod_by')==100?db_result($result, $i, 'email'):user_get_name_display_from_unix(db_result($result, $i, 'user_name'))),
                                                    util_unconvert_htmlspecialchars(db_result($result, $i, 'old_value'))
                                                    );
                                }
                        } else {
                                if ( $comment_type != "" ) {
                                    $out .= sprintf($fmt,
                                                    util_get_alt_row_color($i),
                                                    $comment_type,
                                                    util_make_links(nl2br(db_result($result, $i, 'old_value')),$group_id,$group_artifact_id),
                                                    format_date($sys_datefmt,db_result($result, $i, 'date')),
                                                    (db_result($result, $i, 'mod_by')==100?db_result($result, $i, 'email'):user_get_name_display_from_unix(db_result($result, $i, 'user_name'))));
                                } else {
                                    $out .= sprintf($fmt,
                                                    util_get_alt_row_color($i),
                                                    util_make_links(nl2br(db_result($result, $i, 'old_value')),$group_id,$group_artifact_id),
                                                    format_date($sys_datefmt,db_result($result, $i, 'date')),
                                                    (db_result($result, $i, 'mod_by')==100?db_result($result, $i, 'email'):user_get_name_display_from_unix(db_result($result, $i, 'user_name'))));
                                }
                        }
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
        <B>'.$Language->getText('tracker_include_artifact','fill_cc_list_lbl').'&nbsp;</b><input type="text" name="add_cc" size="30">&nbsp;&nbsp;&nbsp;
        <B>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_artifact','fill_cc_list_cmt').':&nbsp;</b><input type="text" name="cc_comment" size="40" maxlength="255"><p>';
        
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

        
    /**
    * Send different messages to persons affected by this artifact with respect 
	* to their different permissions 
    *
    * @param more_addresses: additional addresses
    * @param changes: array of changes
    *
    * @return void
    */
    function mailFollowupWithPermissions($more_addresses=false,$changes=false) {
        global $sys_datefmt,$art_field_fact,$sys_lf,$Language;
        
        $group = $this->ArtifactType->getGroup();
        $group_artifact_id = $this->ArtifactType->getID();
        $group_id = $group->getGroupId();
        
        // See who is going to receive the notification. Plus append any other email 
        // given at the end of the list.
        $withoutpermissions_concerned_addresses = array();
        $this->buildNotificationArrays($changes, $concerned_ids, $concerned_addresses);
        if ($more_addresses) {
            foreach ($more_addresses as $address) {
                if ($address['address'] && $address['address'] != '') {
                    $res_username = user_get_result_set_from_email($address['address'], false);
                    if ($res_username && (db_numrows($res_username) == 1)) {
                        $u_id = db_result($res_username,0,'user_id');
                        if (!$address['check_permissions']) {
                            $curr_user = new User($u_id);	
                            if ($curr_user->isActive() || $curr_user->isRestricted()) {
                                $withoutpermissions_concerned_addresses[user_getemail($u_id)] = true;
                            }
                            unset($concerned_ids[$u_id]);
                        } else {
                            $concerned_ids[$u_id] = true;
                        }
                    } else {
                        if (!$address['check_permissions']) {
                            $withoutpermissions_concerned_addresses[$address['address']] = true;
                            unset($concerned_addresses[$address['address']]);
                        } else {
                            $concerned_addresses[$address['address']] = true;
                        }
                    }
                }
            }
        }
        //concerned_ids contains users for wich we have to check permissions
        //concerned_addresses contains emails for which there is no existing user. Permissions will be checked (Anonymous users)
        //withoutpermissions_concerned_addresses contains emails for which there is no permissions check
        
        //Prepare e-mail
        list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
        $mail =& new Mail();
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->addAdditionalHeader("X-CodeX-Project",     group_getunixname($group_id));
        $mail->addAdditionalHeader("X-CodeX-Artifact",    $this->ArtifactType->getItemName());
        $mail->addAdditionalHeader("X-CodeX-Artifact-ID", $this->getID());
        
        
	    //treat anonymous users
	    $body = $this->createMailForUsers(array($GLOBALS['UGROUP_ANONYMOUS']),$changes,$group_id,$group_artifact_id,$ok,$subject);
	    
	    if ($ok) { //don't send the mail if nothing permitted for this user group
            $to = join(',',array_keys($concerned_addresses));
            if ($to) { 
                $mail->setTo($to);
                $mail->setSubject($subject);
                $mail->setBody($body);
                $mail->send();
            }
	    }
        
        //treat 'without permissions' emails
        if (count($withoutpermissions_concerned_addresses)) {
            $body = $this->createMailForUsers(false,$changes,$group_id,$group_artifact_id,$ok,$subject);
            if ($ok) {
                $mail->setTo(join(',', array_keys($withoutpermissions_concerned_addresses)));
                $mail->setSubject($subject);
                $mail->setBody($body);
                $mail->send();
            }
        }
        
        //now group other registered users

	    //echo "<br>concerned_ids = ".implode(',',array_keys($concerned_ids));

	    $this->groupNotificationList(array_keys($concerned_ids),$user_sets,$ugroup_sets);

	    //echo "<br>user_sets = "; print_r($user_sets); echo ", ugroup_sets = "; print_r($ugroup_sets);

	    reset($ugroup_sets);
	    while (list($x,$ugroups) = each($ugroup_sets)) {
            unset($arr_addresses);
            
            $user_ids = $user_sets[$x];
            //echo "<br>--->  preparing mail $x for ";print_r($user_ids);
            $body = $this->createMailForUsers($ugroups,$changes,$group_id,$group_artifact_id,$ok,$subject);
            
            if (!$ok) continue; //don't send the mail if nothing permitted for this user group

            foreach ($user_ids as $user_id) {
                $arr_addresses[] = user_getemail($user_id);
            }

            $to = join(',',$arr_addresses);
            if ($to) { 
                $mail->setTo($to);
                $mail->setSubject($subject);
                $mail->setBody($body);
                $mail->send();
            }
	    }
	    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_include_artifact','update_sent')); //to '.$to;
    }


	/** for a certain set of users being part of the same ugroups
	 * create the mail body containing only fields that they have the permission to read
	 */
	function createMailForUsers($ugroups,$changes,$group_id,$group_artifact_id,&$ok,&$subject) {
	  global $art_field_fact,$art_fieldset_fact,$Language,$sys_lf,$sys_datefmt;

	  $fmt_len = 40;
	  $fmt_left = sprintf("%%-%ds ", $fmt_len-1);
	  $fmt_right = "%s";
	  $artifact_href = get_server_url()."/tracker/?func=detail&aid=".$this->getID()."&atid=$group_artifact_id&group_id=$group_id";
	  $used_fields = $art_field_fact->getAllUsedFields();
      $used_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
	  $ok = false;

	  
	    
	  //generate the field permissions (TRACKER_FIELD_READ, TRACKER_FIEDL_UPDATE or nothing)
	  //for all fields of this tracker given the $ugroups the user is part of
      $field_perm = false;
	  if ($ugroups) {
          $field_perm = $this->ArtifactType->getFieldPermissions($ugroups);
      }

	  $summ = "";
	  if (!$field_perm || ($field_perm['summary'] && permission_can_read_field($field_perm['summary']))) {
	    $summ = util_unconvert_htmlspecialchars($this->getValue('summary'));
	  }
	  $subject='['.$this->ArtifactType->getCapsItemName().' #'.$this->getID().'] '.$summ;
	  

	  //echo "<br>......... field_perm for "; print_r($ugroups); echo " = "; print_r($field_perm);

	    // artifact fields
	    // Generate the message preamble with all required
	    // artifact fields - Changes first if there are some.
	    if ($changes) {
		$body = "$sys_lf=============   ".strtoupper($this->ArtifactType->getName())." #".$this->getID().
		    ": ".$Language->getText('tracker_include_artifact','latest_modif')."   =============$sys_lf".$artifact_href."$sys_lf$sys_lf".
		  $this->formatChanges($changes,$field_perm,$visible_change)."$sys_lf$sys_lf$sys_lf$sys_lf";

		if (!$visible_change) return;
	    }
        
	    $ok = true;
	    
            
	    $visible_snapshot = false;
	    $full_snapshot = "";

        // We write the name of the project
        $full_snapshot .= sprintf($fmt_left."$sys_lf",$Language->getText('tracker_include_artifact','project').' '.group_getname($group_id) );
        
	    // Write all the fields, grouped by fieldsetset and ordered by rank.
	    $left = 1;
	    
	    $visible_fieldset = false;
        // fetch list of used fieldsets for this artifact
	    foreach ($used_fieldsets as $fieldset_id => $fieldset) {
            $fieldset_snapshot = '';
            $used_fields = $fieldset->getAllUsedFields();
            // fetch list of used fields and the current field values
            // for this artifact
            while ( list($key, $field) = each($used_fields) ) {

                $field_name = $field->getName();

                if (!$field_perm || ($field_perm[$field_name] && permission_can_read_field($field_perm[$field_name]))) {
            
                    $field_html = new ArtifactFieldHtml($field);
                    
                    $visible_fieldset = true;
                    $visible_snapshot = true;

                    // For multi select box, we need to retrieve all the values
                    if ( $field->isMultiSelectBox() ) {
                      $field_value = $field->getValues($this->getID());
                    } else {
                      $field_value = $this->getValue($field->getName());
                    }
                    $display = $field_html->display($group_artifact_id,
                                  $field_value,false,true,true,true);
                    $item = sprintf(($left? $fmt_left : $fmt_right), $display);
                    if (strlen($item) > $fmt_len) {
                        if (! $left) {
                          $fieldset_snapshot .= "$sys_lf";
                        }
                        $fieldset_snapshot .= sprintf($fmt_right, $display);
                        $fieldset_snapshot .= "$sys_lf";
                        $left = 1;
                    } else {
                        $fieldset_snapshot .= $item;
                        $left = ! $left;
                        if ($left) {
                          $fieldset_snapshot .= "$sys_lf";
                        }
                    }
              }
            
            } // while
            
            if ($visible_fieldset) {
                $full_snapshot .= "$sys_lf";
                $full_snapshot .= ($left?"":"$sys_lf");
                $full_snapshot .= '--- '.$fieldset->getLabel().' ---';
                $full_snapshot .= "$sys_lf";
                $full_snapshot .= $fieldset_snapshot;
            }
        }

	    if ($visible_snapshot) $full_snapshot .= "$sys_lf";

	    $body .= "=============   ".strtoupper($this->ArtifactType->getName())." #".$this->getID().
		": ".$Language->getText('tracker_include_artifact','full_snapshot')."   =============$sys_lf".
		($changes ? '':$artifact_href)."$sys_lf$sys_lf".$full_snapshot;


	    if (! $left) {
	      $body .= "$sys_lf";
	    }
	    
	    // Now display other special fields
        
        // Then output the history of bug comments from newest to oldest
	    $body .= $this->showFollowUpComments($group_id, true);
	    
	    // Then output the CC list
	    $body .= "$sys_lf$sys_lf".$this->showCCList($group_id, $group_artifact_id, true);
	    
	    // Then output the dependencies
	    $body .= "$sys_lf$sys_lf".$this->showDependencies($group_id,$group_artifact_id,true);
	    
	    // Then output the history of attached files from newest to oldest
	    $body .= "$sys_lf$sys_lf".$this->showAttachedFiles($group_id,$group_artifact_id,true);
	    
        // Extract references from the message
        $referenceManager =& ReferenceManager::instance();
        $ref_array = $referenceManager->extractReferencesGrouped($body, $group_id);
        if (count($ref_array) > 0) {
            $body .= $sys_lf.$sys_lf.$Language->getText('tracker_include_artifact','references').$sys_lf;
        }
        foreach ($ref_array as $description => $match_array) {
            $body .= $sys_lf.$description.":".$sys_lf;
            foreach ($match_array as $match => $ref_instance) {
                $reference =& $ref_instance->getReference();
                $body .= ' '.$ref_instance->getMatch().': '.$ref_instance->getFullGotoLink().$sys_lf;
            }
        }
        
	    // Finally output the message trailer
	    $body .= "$sys_lf$sys_lf".$Language->getText('tracker_include_artifact','follow_link');
	    $body .= "$sys_lf".$artifact_href;

	    return $body;
	}

	
        /**
         * Format the changes
         *
         * @param changes: array of changes
	 * @param $field_perm an array with the permission associated to each field. false to no check perms
	 * @param $visible_change only needed when using permissions. Returns true if there is any change 
	 * that the user has permission to see
         *
         * @return string
         */
        function formatChanges($changes,$field_perm,&$visible_change) {
        
            global $sys_datefmt,$art_field_fact,$sys_lf,$Language;
	    $visible_change = false;
        
            reset($changes);
            $fmt = "%20s | %-25s | %s$sys_lf";
        

	    if (!$field_perm || ( 
		(($field_perm['assigned_to'] && permission_can_read_field($field_perm['assigned_to'])) || 
		 ($field_perm['multi_assigned_to'] && permission_can_read_field($field_perm['multi_assigned_to'])) ||
         (!$field_perm['assigned_to'] && !$field_perm['multi_assigned_to'])))) {
	      if (user_isloggedin()) {
		$user_id = user_getid();
		$out_hdr = $Language->getText('tracker_include_artifact','changes_by').' '.user_getrealname($user_id).' <'.user_getemail($user_id).">$sys_lf";
		$out_hdr .= $Language->getText('tracker_import_utils','date').': '.format_date($sys_datefmt,time()).' ('.user_get_timezone().')';
	      } else {
		$out_hdr = $Language->getText('tracker_include_artifact','changes_by').' '.$Language->getText('tracker_include_artifact','anon_user').'        '.$Language->getText('tracker_import_utils','date').': '.format_date($sys_datefmt,time());
	      }
	    }
            //Process special cases first: follow-up comment
	    if ($changes['comment']) {
	      $visible_change = true;
	      $out_com = "$sys_lf$sys_lf---------------   ".$Language->getText('tracker_include_artifact','add_flup_comment')."   ----------------$sys_lf";
	    
	      if (isset($changes['comment']['type']) && $changes['comment']['type'] != $Language->getText('global','none') && $changes['comment']['type'] != '') {
		$out_com .= "[".$changes['comment']['type']."]$sys_lf";
	      }
	      $out_com .= util_unconvert_htmlspecialchars($changes['comment']['add']);
	      unset($changes['comment']);
	    }
        
            //Process special cases first: file attachment
	    if ($changes['attach']) {
	      $visible_change = true;
	      $out_att = "$sys_lf$sys_lf---------------    ".$Language->getText('tracker_include_artifact','add_attachment')."     -----------------$sys_lf";
	      $out_att .= sprintf($Language->getText('tracker_include_artifact','file_name')." %-30s ".$Language->getText('tracker_include_artifact','size').":%d KB$sys_lf",$changes['attach']['name'],
				  intval($changes['attach']['size']/1024) );
	      $out_att .= $changes['attach']['description']."$sys_lf".$changes['attach']['href'];
	      unset($changes['attach']);
	    }
        
            // All the rest of the fields now
            reset($changes);
            while ( list($field_name,$h) = each($changes)) {
	      
	      // If both removed and added items are empty skip - Sanity check
	      if (!$h['del'] && !$h['add'] ||
		  $field_perm && (
		  !$field_perm[$field_name] || 
		  !permission_can_read_field($field_perm[$field_name]))) { continue; }
	      
	      $visible_change = true;
	      $label = $field_name;
	      $field = $art_field_fact->getFieldFromName($field_name);
	      if ( $field ) {
		$label = $field->getLabel();
	      }
	      $out .= sprintf($fmt, $label, $h['del'],$h['add']);
	    } // while
	    
	    if ($out) {
	      $out = "$sys_lf$sys_lf".sprintf($fmt,$Language->getText('tracker_include_artifact','what').'    ',$Language->getText('tracker_include_artifact','removed'),$Language->getText('tracker_include_artifact','added')).
		"------------------------------------------------------------------$sys_lf".$out;
            }
	    
            return($out_hdr.$out.$out_com.$out_att);
	    
        }


}

?>
