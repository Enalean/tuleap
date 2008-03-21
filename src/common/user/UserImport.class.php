<?php

/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require('www/include/account.php');
require_once('common/dao/UserDao.class.php');
require_once('common/include/Error.class.php');

class UserImport extends Error {

    //the group our users is part of
    var $group_id;


    /**
     * Constructor.
     * 
     * @return boolean success.
     */
    function UserImport($group_id) {
        // Error constructor
        $this->Error();
        $this->group_id = $group_id;    
    }

    /** 
     * parse a file in simple text  format containing users to be imported into the project
     * @param string $user_filename (IN): the complete file name of the file to be parsed
     * @param array $parsed_users (OUT): the users parsed in the import file
     *                                   array of the form (column_number => User object)
     * @param string $errors (OUT): string containing explanation what error occurred
     * @return boolean true if parse ok, false if errors occurred
     */
    function parse($user_filename,&$errors,&$parsed_users) {
        global $Language;

        $user_file = fopen($user_filename, "r");
        $ok = true;  
        $user_dao =& new UserDao(CodexDataAccess::instance());
        $user_id_array = array();   // to check the double names or emails
            
        //parsing each line of the file
        while ($ok && !feof($user_file)) {
            $current_user = false;
            $line = trim(fgets($user_file));
            if ($line != "") {
                // check whether non-empty lines contain valid email addresses or valid usernames	
                if (!validate_email($line)) {
                    // It's not an email address, let's assume it is a CodeX username
                    $user_result = $user_dao->searchByUserName($line);
                    if ($user_result && ($user_array =& $user_result->getRow())) {	
                        $current_user = new User($user_array['user_id']);  
                    } else {
                        // this username doesn't exist in codeX   
                        $ok = false;
                        $errors = $Language->getText('project_admin_userimport','invalid_mail_or_username',$line);
                    }
                } else {
                    //check if user exists (has connected, at least once, to Codex)
                    $user_result = $user_dao->searchByEmail($line);
                    $nb_users = $user_result->rowCount();
                    if ($nb_users < 1) {
                        $ok = false;	
                        $errors=$Language->getText('project_admin_userimport','unknown_user',$line);	
                    } elseif ($nb_users > 1) {
                        $ok = false;
                        $errors=$Language->getText('project_admin_userimport','special_user',$line);
                    } else {
                        $user_array =& $user_result->getRow();
                        $current_user = new User($user_array['user_id']);
                    }
                }
                // $current_user contains the user Object we areparsing the name of or the email address 
                if ($ok && $current_user) {
                    // check that the user is active
                    if (! $current_user->isActive()) {
                        $ok = false;
                        $errors=$Language->getText('project_admin_userimport','active_user',$current_user->getRealName()); 	
                    } else {
                        // check that the user is not already memeber of the project
                        if ($current_user->isMember($this->group_id)) {
                        	    $ok = false;
                            $errors=$Language->getText('project_admin_userimport','member_user',$current_user->getRealName());
                        } else {
                            // last check : is the user already in the list of imported users ?                        
                            if (! in_array($current_user->getID(), $user_id_array)) {
                            	    // everything is ok, we can add the user in the list of users to import
                                $parsed_users[] = $current_user;
                                $user_id_array[] = $current_user->getID();
                            }
                        }
                    }
                }
            }
        }

        fclose($user_file);
        return $ok;
    }

    /**
     * Insert the imported users into the db
     * @param array parsed_users: array of the form (column_number => login name) containing
     *                            all the users parsed from import file
     * @return true if parse ok, false if errors occurred
     */
    function updateDB($parsed_users) {
        //add users to the project
        for ($i=0;$i<count($parsed_users);$i++) {
            $res = account_add_user_to_group($this->group_id, $parsed_users[$i]);
    
            if ($res) {
                group_add_history('added_user',$parsed_users[$i],$this->group_id,array($parsed_users[$i]));        
            } else {
                return false;
                exit;
            }    
        }
        return true;
    }

}

?>