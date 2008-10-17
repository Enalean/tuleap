<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * ServerUpdateViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/plugin/PluginManager.class.php');
require_once('common/plugin/PluginHookPriorityManager.class.php');

require_once('SVNUpdate.class.php');
require_once('SVNUpdateFilter.class.php');


define("URL_REPOSITORY_SHOW_DETAILS", "https://partners.xrce.xerox.com/svn/?func=detailrevision&group_id=120&rev_id=");

class ServerUpdateViews extends Views {
    
    /**
     * @var string path to plugin icons
     */
    var $iconsPath;
    
    function ServerUpdateViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $this->iconsPath = $controler->getThemePath().'/images/ic/';
    }
    
    function getIconsPath() {
        return $this->iconsPath;
    }
    
    function header() {
        if ( ! HTTPRequest::instance()->isAjax()) {
            $title = $GLOBALS['Language']->getText('plugin_serverupdate','title');
            $GLOBALS['HTML']->header(array('title'=>$title, 'selected_top_tab' => 'admin'));
            echo '<h2>'.$title.'&nbsp;'.$this->_getHelp().'</h2>';
            echo '<b><a href="index.php">'.$GLOBALS['Language']->getText('plugin_serverupdate_menu','server_update').'</a></b> | ';
            echo '<b><a href="?view=upgrades">'.$GLOBALS['Language']->getText('plugin_serverupdate_menu','script_upgrades').'</a></b> | ';
            echo '<b><a href="?view=preferences">'.$GLOBALS['Language']->getText('plugin_serverupdate_menu','preferences').'</a></b>';
        }
    }
    function footer() {
        if ( ! HTTPRequest::instance()->isAjax()) {
            $GLOBALS['HTML']->footer(array());
        }
    }
    
    // {{{ Views
    function browse() {
        $output = '';
        $output .= $this->_showHeaderUpdates();
        $output .= $this->_showFilters();
        $output .= $this->_showUpdates();
        echo $output;
    }
    function scriptupgrades() {
        $output = '';
        $output .= $this->_showHeaderScriptUpgrades();
        $output .= $this->_showScriptUpgrades();
        echo $output;
    }
    function preferences() {
        $output = '';
        $output .= $this->showPreferences();
    }
    function norepository() {
        $output = '';
        $output .= '<p class="feedback">'.$GLOBALS['Language']->getText('plugin_serverupdate_update','norepository').'</p>';
        echo $output;
    }
    
    
    /**
     * Function testUpdate : simulate the update and execute it if no problem is detected
     * (1) test if no manual update is necessary between the current revision and the required revision
     * (2) if not, simulate the update whished (in one time)
     * (3) if no problem detected, offer the user to process the update
     */
    function testUpdate() {
        $request =& HTTPRequest::instance();
        $revision = $request->get('revision');
        
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        
        $output = '';

        echo '<h3>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','SimulatingUpdate', array($svnupdate->getWorkingCopyRevision(), $revision)).'</h3>';
        
        //
        // Test if a manual update is necessary between the current revision and the required revision
        //
        $commits = $svnupdate->getSVNCommitsBetween($svnupdate->getWorkingCopyRevision(), $revision);
        $needManualUpdate = false;
        $commitManualUpdate = null;
        $i = 0;
        while ($i < count($commits) && (!$needManualUpdate)) {
            $commit = $commits[$i]; 
            if ($commit->needManualUpdate()) {
                $needManualUpdate = true;
                $commitManualUpdate = $commit;
            }
            $i++;
        }
        
        if (!$needManualUpdate) {
            
            //
            // Test if scripts files are present in the asked update
            // And if any problem will occur with scripts
            //
            $scriptProblem = false;
            
            // These two cases are ok
            $scriptsToBeExecuted = array();
            $scriptsToBeIgnored = array();
            // These two cases are not ok => error
            $scriptsAlreadyExecuted = array();
            $scriptsModifiedInFurtherRevision = array();
            $scriptsDeletedInFurtherRevision = array();
            
            $scriptsLifeCycle = $svnupdate->getScriptsLifeCycle();
            foreach($scriptsLifeCycle as $scriptName => $scriptLifeCycle) {
                $revisions_keys = array_keys($scriptLifeCycle);
                $first_used_revision = $revisions_keys[0];
                // We are treating only the scripts that have activity during the asked update
                if ($first_used_revision <= $revision) {
                    if ($scriptLifeCycle[$first_used_revision] != "A") {
                        // the script have been created before the first revision we intend to update,
                        // so this means that there is potentially a problem.
                        // So we don't allow the update by the web interface and advise a manbual update
                        $scriptsAlreadyExecuted[$scriptName] = null;
                    } else {
                        // The script is created in the current update
                        $last_modified_revision = $revisions_keys[count($revisions_keys)-1];
                        if ($last_modified_revision > $revision) {
                            // the script is modified after the current update.
                            // We don't allow this situation and will ask the user
                            // to update at least up to this 'last modified' revision
                            if ($scriptLifeCycle[$last_modified_revision] == "D") {
                                $scriptsDeletedInFurtherRevision[$scriptName] = $last_modified_revision;
                            } else {
                                $scriptsModifiedInFurtherRevision[$scriptName] = $last_modified_revision;
                            }
                        } else {
                            // the last modification of the script in inclued in the asked update
                            // So we will execute the script (if the last modification is an modification,
                            // or ignore the script if the last modification is a delete
                            if ($scriptLifeCycle[$last_modified_revision] == "D") {
                                $scriptsToBeIgnored[$scriptName] = $last_modified_revision;
                            } else {
                                $scriptsToBeExecuted[$scriptName] = $last_modified_revision;
                            }
                        }
                    }
                }
            }            
            
            $scriptProblem = ((count($scriptsAlreadyExecuted) != 0) || (count($scriptsModifiedInFurtherRevision) != 0) || (count($scriptsDeletedInFurtherRevision) != 0));
            
            if (!$scriptProblem) {
            
                //
                // Simulating the update from the current revision up to the required revision.
                //
                $this->_displayWaitingImage();
                
                $test_output = '';//$this->_simulateUpdate($revision);
                echo '<pre>'.$test_output.'</pre>';
                
                $this->_hideWaitingImage();
                
                // we have to test if there is a SVN Conflit
                $conflited_files = array();
                $conflited_files = SVNUpdate::getConflictedLines($test_output);
                if (count($conflited_files) > 0) {
                    $output .= '<p class="feedback"><strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ConflictOnFile').'</strong></p>';
                    $output .= '<ul>';
                    foreach ($conflited_files as $c_file) {
                        $output .= '<li class="feedback"><strong>'.$c_file.'</strong></li>';
                    }
                    $output .= '</ul>';
                    
                    $output .= '<p class="feedback"><strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','UpdateManually', $svnupdate->getWorkingCopyDirectory()).'</strong></p>';
                    $output .= '<p><code>svn update -r '.$revision.'</code></p>';
                } else {
                    echo $GLOBALS['Language']->getText('plugin_serverupdate_update','NoConflicts');
                    // We give information about scripts if there are.
                    if (count($scriptsToBeIgnored) > 0 || count($scriptsToBeExecuted) > 0) {
                        echo '<p>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptsInfos').'<p>';
                        if (count($scriptsToBeIgnored) > 0) {
                            echo $GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptsIgnored');
                            echo '<ul>';
                            foreach($scriptsToBeIgnored as $key => $value) {
                                echo '<li>'.$key.' '.$GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptDeletedAt', $value).'</li>';
                            }
                            echo '</ul>';
                        }
                        if (count($scriptsToBeExecuted) > 0) {
                            echo $GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptsExecuted');
                            echo '<ul>';
                            foreach($scriptsToBeExecuted as $key => $value) {
                                echo '<li>'.$key.' '.$GLOBALS['Language']->getText('plugin_serverupdate_update','AtRevision', $value).'</li>';
                            }
                            echo '</ul>';
                        }
                    }
                    
                    //
                    // there is no conflict, so we can offer the user the possibility of updating the server
                    //
                    echo '<form action="index.php" method="post">';
                    echo '<input type="hidden" name="revision" value="'.$revision.'" />';
                    echo '<input type="hidden" name="action" value="processUpdate" />';
                    echo $GLOBALS['Language']->getText('plugin_serverupdate_update','ConfirmUpdate');
                    echo '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ProcessUpdate').'" /><br />';
                    echo '</form>';
                    echo $GLOBALS['Language']->getText('plugin_serverupdate_update','CancelUpdate');
                }
            } else {
                
                $output .= '<p class="highlight"><b>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptsErrors').'</b></p>';
                $output .=  '<p class="highlight">'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptsInTrouble').'</p>';
                // There is 3 kind of problem :
                $output .= '<ul class="feedback">';
                // 1) the script is modified in a further revision, and the user is not updating upt o this further revision
                foreach($scriptsModifiedInFurtherRevision as $key => $value) {
                    $output .= '<li class="feedback">'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptMustBeExecutedAtRevison', array($key, $value)).'</li>';
                }
                // 2) the script is deleted in a further revision, and the user is not updating upt o this further revision
                foreach($scriptsDeletedInFurtherRevision as $key => $value) {
                    $output .= '<li class="feedback">'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptMustBeDeletedAtRevison', array($key, $value)).'</li>';
                }
                // 3) the script has already been executed in a precedent revision, and the script is now modified.
                foreach($scriptsAlreadyExecuted as $key => $value) {
                    $output .= '<li class="feedback">'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptAlreadyExecuted', array($key, $svnupdate->getWorkingCopyRevision())).'</li>';
                }
                $output .= '</ul>';
                if (count($scriptsAlreadyExecuted) > 0) {
                    $output .= '<p class="highlight"><b>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ManualUpdateRequired').'</b></p>';
                }
            }
        } else {
            $output .= '<p class="highlight"><b>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','RevisionNeedManualUpdate', $commitManualUpdate->getRevision()).'</b></p>';
            $output .= '<pre>'.$commitManualUpdate->getMessage().'</pre>';
            
            // There is a manual update, so normally, we shouldn't allow the update
            // However, the user can FORCE the update (and so he assume its acts)
            
            $output .= '<form action="index.php" method="post">';
            $output .= '<input type="hidden" name="revision" value="'.$revision.'" />';
            $output .= '<input type="hidden" name="action" value="processUpdate" />';
            $output .= '<input type="hidden" name="force" value="true" />';
            $output .= $GLOBALS['Language']->getText('plugin_serverupdate_update','ForceUpdate');
            $output .= '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ForceUpdateButton').'" /><br />';
            $output .= '</form>';
            $output .= $GLOBALS['Language']->getText('plugin_serverupdate_update','CancelUpdate');
            
        }
        
        $output .= '<p><a href="index.php">'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ReturnToUpdates').'</a></p>';
        echo $output;
    }

    /**
     * Function processUpdate
     *
     * Must be called after a simulation, when no errors are detected
     * (1) execute an incremental update (one revision by one) testing each time if the update end well
     * (2) for each file of the commit, if the file is a script to execute, execute it, testing each time if the script executed well.
     */
    function processUpdate() {
        $request =& HTTPRequest::instance();
        $revision = $request->get('revision');
        
        $forceUpdate = false;
        if ($request->exist('force')) {
            $forceUpdate = $request->get('force') == "true";
        }
        
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();

        $output = '';

        echo '<h3>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','UpdatingServer', array($svnupdate->getWorkingCopyRevision(), $revision)).'</h3>';
                
        // We are processing an incremental update, from the first non-updated revision up to the choosen revision
        $current_revision = $svnupdate->getWorkingCopyRevision();
        $commits_to_update = $svnupdate->getSVNCommitsBetween($current_revision, $revision);
        $interrupt_update_process = false;
        $forcedRevisions = array();
        foreach ($commits_to_update as $commit_to_update) {
            if (!$interrupt_update_process) {
                
                echo '<h4>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','UpdatingServer', array($current_revision, $commit_to_update->getRevision())).'</h4>';
                
                // We are recording the forced revisions, to recall the user that he has forced the update
                if ($commit_to_update->needManualUpdate() && $forceUpdate) {
                    $forcedRevisions[] = $commit_to_update->getRevision();
                }
                
                if (!$commit_to_update->needManualUpdate() || $forceUpdate) {
                    
                    $this->_displayWaitingImage();
                
                    // Update the server to the next revision
                    $update_output = $this->_updateServer($commit_to_update->getRevision());
                    echo '<pre>'.$update_output.'</pre>';
                    
                    $this->_hideWaitingImage();
                    
                    $conflited_lines = array();
                    $conflited_lines = SVNUpdate::getConflictedLines($update_output);
                    if (count($conflited_lines) > 0) {
                        $interrupt_update_process = true;
                        $update_process_issue = '<b>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ErrorDuringUpdateCarefull').'</b>';
                    }
                    
                    // Test if there is a script to execute within the commited files of this current commit
                    foreach ($commit_to_update->getFiles() as $file) {
                        // if the file is deleted, we skip it
                        if ($file->getAction() != "D") {
                            // if the file is not a script, we skip it
                            if (is_a($file, 'UpgradeScript')) {
                                echo $GLOBALS['Language']->getText('plugin_serverupdate_update','revision_contains_script').'<br />';
                                // if the script is present in a further revision, we skip it
                                if (!$svnupdate->isPresentInFurtherRevision($file, $commit_to_update->getRevision())) {
                                    // We check if the script respect the script-rules
                                    if ($file->isWellImplemented()) {
                                        echo $GLOBALS['Language']->getText('plugin_serverupdate_update','script_execution', $file->getClassname()).'<br />';
                                        $errors = $file->execute();
                                        if (count($errors) == 0) {
                                            echo $GLOBALS['Language']->getText('plugin_serverupdate_script','exec_success');
                                        } else {
                                            $interrupt_update_process = true;
                                            $update_process_issue = '<p class="feedback"><b>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ErrorDuringScriptUpgrade', $file->getClassname()).'</b></p>';
                                            $update_process_issue .= '<ul>';
                                            foreach($errors as $error) {
                                                $update_process_issue .= '<li class="feedback">'.$error.'</li>';
                                            }
                                            $update_process_issue .= '</ul>';
                                        }
                                    } else {
                                        $interrupt_update_process = true;
                                        $update_process_issue = '<p class="feedback"><strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptNotWellImplemented', $file->getClassname()).'</strong></p>';
                                    }
                                } else {
                                    echo $GLOBALS['Language']->getText('plugin_serverupdate_update','ScriptSkipped', $file->getClassname());
                                }
                            }
                        }
                    }
                    
                    $current_revision = $commit_to_update->getRevision();
                } else {
                    $interrupt_update_process = true;
                    $update_process_issue = '<p class="feedback"><b>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','RevisionNeedManualUpdate', $commit_to_update->getRevision()).'</b></p>';
                    $update_process_issue .= '<pre class="feedback">'.$commit_to_update->getMessage().'</pre>';
                }
            }
        }
        
        if (!$interrupt_update_process) {
            $output .= $GLOBALS['Language']->getText('plugin_serverupdate_update','UpdateDone');
        } else {
            $output .= '<p class="feedback"><b>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ErrorDuringUpdate').'</b></p>';
            $output .= $update_process_issue;
        }
        
        if (count($forcedRevisions) > 0) {
            $output .= '<p class="feedback"><b>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','RememberUpdateForced', implode(", ",$forcedRevisions)).'</b></p>';
        }
        
        $output .= '<p><a href="index.php">'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ReturnToUpdates').'</a></p>';
        echo $output;
        
    }
    
    // }}}
    
    
    function _getHelp($section = '') {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        return '<a href="javascript:help_window(\''.get_server_url().'/plugins/serverupdate/documentation/'.$GLOBALS['Language']->getLanguageCode().'/'.$section.'\');">[?]</a>';
    }
    
    /**
     * Display a div element with an image inside.
     *
     * Trick for displaying an image in javascript, and then make it disapear.
     * This works even if javascript is not actived. In this case, a text message is written.
     *
     * @param string $div_id the id of the div element used to encapsulate the image
     * @param string $src url of the image we want to display.
     * @param string $alt alternate text for the image
     * @param string $alt_noscript the text to display if javascript is not activated 
     */
    function _displayTemporaryImage($div_id, $src, $alt, $alt_noscript) {
        echo '<div id="'.$div_id.'"></div>';
        echo '<script language="javascript">
                temporaryImage = new Image();
                temporaryImage.src = "'.$src.'";
                temporaryImage.alt = "'.$alt.'";
                document.getElementById("'.$div_id.'").appendChild(temporaryImage);
              </script>';
        echo '<noscript>'.$alt_noscript.'</noscript>';
        flush();    // to minimize the user waiting time
    }
    
    /**
     * Hide the div element of id $div_id.
     *
     * Trick for displaying an image in javascript, and then make it disapear.
     * This works even if javascript is not actived. In this case, a text message is written.
     *
     * Warning : the flush() function supposed to send the current to the browser doesn't work well with IE browser
     *
     * @param string $div_id the id of the div element used to encapsulate the image we want to hide
     */
    function _hideTemporaryImage($div_id) {
        echo '<script language="javascript">document.getElementById("'.$div_id.'").style.display = "none";</script>';
        flush();
    }
    
    /**
     * Call the Javascript function to display the progressBar animation
     */
    function _displayWaitingImage() {
        $this->_displayTemporaryImage("progressBar", $this->getIconsPath()."CodeXProgressBar.gif", $GLOBALS['Language']->getText('plugin_serverupdate_update','UpdateInProgress'), $GLOBALS['Language']->getText('plugin_serverupdate_update','UpdateInProgress'));
    }
    /**
     * Call the Javascript function to hide the progressBar animation
     */
    function _hideWaitingImage() {
        $this->_hideTemporaryImage("progressBar");
    }
    
    /**
     * Display the available update from the last working copy update
     *
     * @return string the HTML code for the list of available updates
     */
    function _showFilters() {
        $Language =& $GLOBALS['Language'];
        $output = '';
        
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        $commits = $svnupdate->getCommits();
        
        $filter = new SVNUpdateFilter();
        
        if (isset($GLOBALS['sort'])) {
            $filter->addCriteria('level', $GLOBALS['level']);
        }
        
        $output .= $filter->getHtmlForm();
        
        return $output;
    }
    
    
    /**
     * Returns the HTML code of the header text before the list of updates.
     *
     * @return string the HTML code of the header text before the list of updates.
     */
    function _showHeaderUpdates() {
        $Language =& $GLOBALS['Language'];
        $output = '';
        
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        $commits = $svnupdate->getCommits();
        
        $output .= '<p>';
        $output .= $Language->getText('plugin_serverupdate_update', 'WorkingCopyRevision', $svnupdate->getWorkingCopyRevision()).'<br />';
        $output .= $Language->getText('plugin_serverupdate_update', 'NumberOfUpdates', count($svnupdate->getCommits())).'<br />';
        $output .= $Language->getText('plugin_serverupdate_update', 'RepositoryIs', $svnupdate->getRepository());
        $output .= '</p>';
        
        return $output;
    }
    
    /**
     * Return the HTML code representing the available updates from the last working copy update
     *
     * @return string the HTML code for the list of available updates
     */
    function _showUpdates() {
        $Language =& $GLOBALS['Language'];
        
        $output = '';
        
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        $commits = $svnupdate->getCommits();
        
        if (isset($GLOBALS['sort'])) {
            $filter = new SVNUpdateFilter();
            $filter->addCriteria('level', $GLOBALS['level']);
            $commits = $filter->apply($commits);
        }
        
        if (is_array($commits)) {   // if there is an error, $commit is not an array (== false)
            if (count($commits) > 0) {
            
                $output .= '<fieldset class="serverupdate"><legend>'.$Language->getText('plugin_serverupdate_update','Updates').'&nbsp;'.$this->_getHelp('manage').'</legend><form>';
                $titles = array();
                $titles[] = $Language->getText('plugin_serverupdate_update','Revision');
                $titles[] = $Language->getText('plugin_serverupdate_update','Date');
                $titles[] = $Language->getText('plugin_serverupdate_update','Message');
                $titles[] = $Language->getText('plugin_serverupdate_update','Details');
                $titles[] = $Language->getText('plugin_serverupdate_update','Special');
                $titles[] = $Language->getText('plugin_serverupdate_update','Actions');
                $output .= html_build_list_table_top($titles);
                
                $hp = CodeX_HTMLPurifier::instance();

                $i=0; // for color alternance
                foreach($commits as $commit) {
                    $metadata = $commit->getMetaData();
                    $output .= '<tr class="'.$metadata->getLevelClass().'" >';
                    //Revision
                    $output .= '<td class="pluginsadministration_plugin_descriptor"><span class="pluginsadministration_name_of_plugin">'.$commit->getRevision().'</span></td>';
                    //Date
                    $output .= '<td class="pluginsadministration_plugin_descriptor">'.util_sysdatefmt_to_userdateformat(util_ISO8601_to_date($commit->getDate())).'</td>';
                    //Message
                    $output .= '<td>';
                    $output .= $hp->purify($commit->getMessage(), CODEX_PURIFIER_BASIC);
                    $output .= '<p><u>'.$Language->getText('plugin_serverupdate_update','ChangedFiles').'</u>';
                    $output .= '<ul>';
                    $files = $commit->getFiles();
                    foreach ($files as $file) {
                        $output .= '<li>';
                        $output .= '(<strong>'.$file->getAction().'</strong>) '.$file->getPath();
                        $output .= '</li>';
                    }
                    $output .= '</ul>';
                    $output .= '</p>';
                    $output .= '</td>';
                    //Details
                    $output .= '<td>';
                    $output .= '<a href="'.URL_REPOSITORY_SHOW_DETAILS.$commit->getRevision().'">'.$Language->getText('plugin_serverupdate_update','detail_link').'</a>';
                    $output .= '</td>';
                    //Specials
                    $output .= '<td>';
                    if ($commit->needManualUpdate()) {
                        $output .= '<img src="'.$this->getIconsPath().'manual_update.png" title="'.$GLOBALS['Language']->getText('plugin_serverupdate_update','NeedManualUpdate').'" alt="'.$GLOBALS['Language']->getText('plugin_serverupdate_update','NeedManualUpdate').'"/>';
                    }
                    if ($commit->containsDBUpdate()) {
                        $output .= '<img src="'.$this->getIconsPath().'database_refresh.png" title="'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ContainsDBUpdate').'" alt="'.$GLOBALS['Language']->getText('plugin_serverupdate_update','ContainsDBUpdate').'"/>';
                    }
                    foreach ($files as $file) {
                        if ($svnupdate->getRevisionInWhichFileMustBeExecuted($file->getPath()) == $commit->getRevision()) {
                            $output .= $file->showSpecials($this->getIconsPath());
                        }
                    }
                    $output .= '</td>';
                    //Actions
                    $output .= '<td>';
                        //Test Update
                    $output .=   '<a class="serverupdate_action" href="?action=testupdate&revision='.$commit->getRevision().'" title="'.$Language->getText('plugin_serverupdate_update','UpdateAction').'">';
                    $output .=     '<img src="'.$this->getIconsPath().'convert.gif" border="0" alt="'.$Language->getText('plugin_serverupdate_update','UpdateAction').'" />';
                    $output .=   '</a>';
                    
                    $output .= '</td>';
                    $output .= '</tr>';
                    $i++;
                }
                $output .= '</table>';
                $output .= '</form></fieldset>';
                
                $output .= '<p class="small"><strong>'.$Language->getText('plugin_serverupdate_update','prio_colors').'</strong>';
                $output .= '<table><tr>';
                $svn_meta_data = new SVNCommitMetaData();
                $levels = $svn_meta_data->getAvailableLevels();
                foreach($levels as $level) {
                    $svn_meta_data->setLevel($level);
                    $output .=  '<td class="'.$svn_meta_data->getLevelClass().'">&nbsp;'.$level.'&nbsp;</td>';
                }
                $output .=  '</tr></table>';
                
            } else {
                if (isset($GLOBALS['sort'])) {
                    $output .= '<B>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','NoCommitsCriteria').'</B>';
                } else {
                    $output .= '<B>'.$GLOBALS['Language']->getText('plugin_serverupdate_update','UpToDate').'</B>';
                }
            }
        }
        return $output;
    }
    
    /**
     * Simulate the update from current working copy revision to $revision
     *
     * @param int $revision the revision number up to update
     * @return string the result of the simulation (the SVN output of the merge command)
     */
    function _simulateUpdate($revision) {
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        
        $merge_result = '';
        $merge_result .= $svnupdate->testUpdate($revision);
        return $merge_result;
    }
    
    /**
     * Perform the update up to the revision $revision
     *
     * @param int $revision the revision number up to update
     * @return string the result of the update (the SVN output of the update command)
     */
    function _updateServer($revision) {
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        
        $update_result = '';
        $update_result .= $svnupdate->updateServer($revision);
        
        return $update_result;
    }

    
    /**
     * Returns the header text before the list of script upgrades.
     *
     * @return string the header text before the list of script upgrades.
     */
    function _showHeaderScriptUpgrades() {
        $Language =& $GLOBALS['Language'];
        $output = '';
        
        $output .= '<p>';
        $output .= '</p>';
        
        return $output;
    }
    
    /**
     * Returns the javascript functions needed in the script upgrades pages
     * - function showHide to expandCollapse the script execution details
     * 
     * @return string the javascript functions needed in the script upgrades pages
     */
    function get_JS_ScriptUpgrades() {
        $out = '';
        $out .= "<script language='javascript'>";
        $out .= " function showHide(elem) {";
        $out .= "  if (document.getElementById(elem).style.display == 'none') {";
        $out .= "   document.getElementById(elem).style.display = '';";
        $out .= "  } else {";
        $out .= "   document.getElementById(elem).style.display = 'none';";
        $out .= "  }";
        $out .= " }";
        $out .= "</script>";
        return $out;
    }
    
    /**
     * Display the scripts available in the script directory,
     * with several indications (status, execution, date, etc ...)
     *
     * @return string the HTML code for the list of available upgrades
     */
    function _showScriptUpgrades() {
        $Language =& $GLOBALS['Language'];
        $output = '';
        
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        
        $upgrades = $svnupdate->getAllUpgrades();
        
        if (count($upgrades) > 0) {
        
            $output .= $this->get_JS_ScriptUpgrades();
            
            $output .= '<fieldset class="serverupdate"><legend>'.$Language->getText('plugin_serverupdate_script', 'Script_Upgrades').'&nbsp;'.$this->_getHelp('manage').'</legend><form>';
            $titles = array();
            //$titles[] = 'level';
            $titles[] = $Language->getText('plugin_serverupdate_script','Scripts');
            $titles[] = $Language->getText('plugin_serverupdate_script','Status');
            $output .= html_build_list_table_top($titles);
            
            $i=0; // for color alternance
            foreach($upgrades as $upgrade) {
                $output .= '<tr class="'.util_get_alt_row_color($i).'" >';
                //Script
                $output .= '<td class="pluginsadministration_plugin_descriptor"><span class="pluginsadministration_name_of_plugin">'.$upgrade->getClassname().'</span>';
                
                $executions = $upgrade->getExecutions();
                if (count($executions) > 0) {
                    
                    $output .= ' <a href="?view=upgrades&open='.$upgrade->getClassname().'&opens='.(isset($GLOBALS['opens'])?$GLOBALS['opens']:"").'" onclick="showHide(';
                    $output .= "'executions".$upgrade->getClassname()."'); return false;";
                    $output .= '">'.$Language->getText('plugin_serverupdate_script','show_hide_execs').'</a>';
                    //Executions
                    $output .= '<div id="executions'.$upgrade->getClassname().'" ';
                    if (isset($GLOBALS['opens'])) {
                        $opens = explode(",", $GLOBALS['opens']);
                        if (in_array($upgrade->getClassname(), $opens)) {
                            $output .= '>';
                        } else {
                            $output .= 'style="display:none">';
                        }
                    } else {
                        $output .= 'style="display:none">';
                    }
                    
                    $titles_exec = array();
                    $titles_exec[] = '';
                    $titles_exec[] = $Language->getText('plugin_serverupdate_script','exec_date');
                    $titles_exec[] = $Language->getText('plugin_serverupdate_script','exec_mode');
                    $titles_exec[] = $Language->getText('plugin_serverupdate_script','exec_status');
                    $titles_exec[] = $Language->getText('plugin_serverupdate_script','exec_errors');
                    $output .= html_build_list_table_top($titles_exec);
                    $j=0;   // for color alternance
                    foreach ($executions as $exec) {
                        $output .= '<tr class="'.util_get_alt_row_color($j).'" >';
                        // Status icon (success or error)
                        if ($exec->getSuccessfullyApplied()) {
                            $output .= ' <td><img src="'.$this->getIconsPath().'success.png" alt="Success" /></td>';
                        } else {
                            $output .= ' <td><img src="'.$this->getIconsPath().'error.png" alt="Error" /></td>';
                        }
                        // date
                        $output .= ' <td>'.util_timestamp_to_userdateformat($exec->getDate()).' </td>';
                        // execution mode (web, console, ...)
                        $output .= ' <td>'.$exec->getExecutionMode().' </td>';
                        // Status (Success, error, ...)
                        $output .= ' <td>';
                        if ($exec->getSuccessfullyApplied()) {
                            $output .= $Language->getText('plugin_serverupdate_script','exec_success');
                        } else {
                            $output .= $Language->getText('plugin_serverupdate_script','exec_failure');
                        }
                        $output .= '</td>';
                        // Errors if necessary
                        $output .= ' <td>'.($exec->getErrors()?$exec->getErrors():"- - -").' </td>';
                        $output .= '</tr>';
                        $j++;
                    }
                    $output .= '</table>';
                }
                $output .= "</div>";
                    //
                $output .= '</td>';
                //Status
                $output .= '<td>';
                if ($upgrade->hasBeenSuccessfullyApplied()) {
                    $status = $Language->getText('plugin_serverupdate_script','status_applied');
                } else {
                    if (count($upgrade->getExecutions()) == 0) {
                        $status = $Language->getText('plugin_serverupdate_script','status_never_applied');
                    } else {
                        $status = $Language->getText('plugin_serverupdate_script','status_applied_with_errors');
                    }
                }
                $output .= $status;
                $output .= '</td>';
                $output .= '</tr>';
                $i++;
            }
            $output .= '</table>';
            $output .= '</form></fieldset>';
        } else {
            $output .= '<B>'.$GLOBALS['Language']->getText('plugin_serverupdate_script','NoUpgrades').'</B>';
        }
        return $output;
    }
    
    
    
    /**
     * Execute the script $GLOBALS['scriptname']
     * and write the execution feedback
     * Function called in the Script upgrade interface (not called in the server update script)
     */
    function executeScript() {
        $Language =& $GLOBALS['Language'];
        $output = '';
        
        $scriptname = $GLOBALS['scriptname'];
        
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        $upgrades = $svnupdate->getAllUpgrades();
        foreach($upgrades as $upgrade) {
            if ($upgrade->getClassname() == $scriptname) {
                $upgrade_to_execute = $upgrade;
            }
        }
        $output .= '<p class="feedback">';
        if (isset($upgrade_to_execute)) {
            $errors = $upgrade_to_execute->execute();
            if (count($errors) == 0) {
                // Upgrade successed
                $output .= '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_script','UpgradeSuccessfullyApplied', $upgrade_to_execute->getClassname()).'</strong><br />';
            } else {
                // Errors
                $output .= '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_script','UpgradeScriptFailed', $upgrade_to_execute->getClassname()).'</strong><br />';
                $output .= '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_script','ExecutionErrors').'</strong>';
                $output .= '<ul>';
                foreach($errors as $error) {
                    $output .= '<li class="feedback"><strong>'.$error.'</strong></li>';
                }
                $output .= '</ul>';
            }
        } else {
            $script_dir = $svnupdate->getWorkingCopyDirectory().'/'.UPGRADE_SCRIPT_PATH;
            $output .= '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_script','ScriptNotFound', array($scriptname, $script_dir)).'</strong>';
        }
        $output .= '</p>';
        echo $output;
    }
    
    function showPreferences() {
        $controler = $this->getControler();
        $svnupdate = $controler->getSVNUpdate();
        
        $output = '';
        $output .= '<h3>'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','preferences_title').'</h3>';
        $output .= '<p>';
        $output .= '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','svn_repository').'</strong> ';
        if ($svnupdate->getRepository() != "") {
            $output .= $svnupdate->getRepository();
        } else {
            $output .= '<span class="error">'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','norepository').'</span>';
        }
        $output .= '<br /></p>';
        $output .= '<p>';
        $output .= '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','working_directory').'</strong> '.$svnupdate->getWorkingCopyDirectory().'<br />';
        $output .= '</p>';
        $output .= '<p>';
        $output .= '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','script_directory').'</strong> '.$svnupdate->getWorkingCopyDirectory().'/'.UPGRADE_SCRIPT_PATH.'<br />';
        $output .= '</p>';
        echo $output;
    }

}


?>
