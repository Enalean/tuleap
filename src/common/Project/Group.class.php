<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/*

    Group object by Tim Perdue, August 28, 2000

    Sets up database results and preferences for a group and abstracts this info

    Project.class.php call this.

    Project.class.php contains all the deprecated API from the old group.php file



    GENERALLY YOU SHOULD NEVER CALL THIS OBJECT
    USE Project instead
    DIRECT CALLS TO THIS OBJECT ARE NOT SUPPORTED

*/

function group_get_object_by_name($groupname)
{
    $pm = ProjectManager::instance();

    return $pm->getProjectByUnixName($groupname);
}

/**
 * @psalm-type ProjectID = int|string
 */
class Group //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    //associative array of data from db
    public $data_array;

    public $group_id;

    //database result set handle
    public $db_result;

    //permissions data row from db
    public $perm_data_array;

    //membership data row from db
    public $members_data_array;

    //whether the user is an admin/super user of this project
    public $is_admin;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;

    public function __construct($param)
    {
            //$param can be:
            // - a row from the groups table -> use it
            // - a group_id -> retrieve row from table
        if (is_array($param)) {
            $this->group_id   = $param['group_id'];
            $this->data_array = $param;
        } elseif (intval($param) > 0) {
            $this->group_id  = (int) $param; // TODO db_es()?
            $this->db_result = db_query("SELECT * FROM `groups` WHERE group_id=" . db_ei($this->group_id));
            if (db_numrows($this->db_result) < 1) {
             //function in class we extended
                $this->setError($GLOBALS['Language']->getText('include_group', 'g_not_found'));
                $this->data_array = [];
            } else {
             //set up an associative array for use by other functions
                $this->data_array = db_fetch_array($this->db_result);
            }
        } else {
            $this->setError('');
            $this->data_array = [];
        }
    }


    /*
        Return database result handle for direct access

        Generall should NOT be used - here for supporting deprecated group.php
    */
    public function getData()
    {
        return $this->db_result;
    }


    /*
        Simply return the group_id for this object
    */
    public function getGroupId()
    {
        return $this->group_id;
    }


    /*
        Project, template, test, etc
    */
    public function getType()
    {
        return $this->data_array['type'];
    }

    /**
     * Statuses include H,A,D
     *
     * @psalm-mutation-free
     */
    public function getStatus()
    {
        return $this->data_array['status'];
    }

    /**
     * Simple boolean test to see if it's a project or not
     *
     * @return bool
     */
    public function isProject()
    {
        $template = $this->getTemplateSingleton();
        return $template->isProject($this->data_array['type']);
    }

    public function isActive()
    {
        return $this->getStatus() == 'A';
    }

    public function isDeleted()
    {
        return $this->getStatus() == 'D';
    }

    public function isSystem()
    {
        return $this->getStatus() == 's' || $this->getStatus() == 'S';
    }

    /**
     * @psalm-mutation-free
     */
    public function getUnixName($tolower = true)
    {
        return $tolower ? $this->getUnixNameLowerCase() : $this->getUnixNameMixedCase();
    }

    /**
     * @psalm-mutation-free
     */
    public function getUnixNameLowerCase()
    {
        return strtolower($this->getUnixNameMixedCase());
    }

    /**
     * @psalm-mutation-free
     * @psalm-taint-escape file
     * @psalm-taint-escape header
     * @psalm-taint-escape ssrf
     */
    public function getUnixNameMixedCase()
    {
        return $this->data_array['unix_group_name'];
    }

    public function getUrl()
    {
        return '/projects/' . urlencode($this->getUnixNameMixedCase());
    }

    /**
     * @psalm-mutation-free
     */
    public function getPublicName()
    {
        return $this->data_array['group_name'];
    }

    //short description as entered on the group admin page
    public function getDescription()
    {
        return $this->data_array['short_description'];
    }


    //date the group was registered
    public function getStartDate()
    {
        return $this->data_array['register_time'];
    }

    public function getHTTPDomain()
    {
        return $this->data_array['http_domain'];
    }

    /**
     * @return ProjectID|null
     *
     * @psalm-ignore-nullable-return
     * @psalm-mutation-free
     */
    public function getID()
    {
        if (isset($this->data_array['group_id'])) {
            return $this->data_array['group_id'];
        }

            return null;
    }

    /**
     *    getUnixGID - return the Unix GID for this group.
     *
     *    @return int GID.
     */
    public function getUnixGID()
    {
        return $this->data_array['group_id'] + ForgeConfig::get('unix_gid_add');
    }

    /**
     *    getMembersId - Return an array of user ids of group members
     *
     *    @return int group_id.
     */
    public function getMembersId()
    {
        if ($this->members_data_array) {
     //list of members already built
        } else {
            $res = db_query("SELECT user_id FROM user_group WHERE group_id='" . db_ei($this->getGroupId()) . "'");
            if ($res && db_numrows($res) > 0) {
                    $mb_array = [];
                while ($row = db_fetch_array($res)) {
                    $mb_array[] = $row[0];
                }
                $this->members_data_array = $mb_array;
            } else {
                    echo db_error();
                    $this->members_data_array = [];
            }
            db_free_result($res);
        }
        return $this->members_data_array;
    }

    protected $members_usernames_data_array;
    /**
     * getMembersUserNames - Return an array of user names of group members
     */
    public function getMembersUserNames(?ProjectManager $pm = null)
    {
        if (! $this->members_usernames_data_array) {
            if (is_null($pm)) {
                $pm = ProjectManager::instance();
            }
            $this->members_usernames_data_array = $pm->getProjectMembers($this->getGroupId());
        }
        return $this->members_usernames_data_array;
    }


    /*

        Basic user permissions that apply to all Groups

    */


    /*
        Simple test to see if the current user is a member of this project
    */
    public function userIsMember($field = 'user_id', $value = 0)
    {
        if ($this->userIsAdmin()) {
     //admins are tested first so that super-users can return true
     //and admins of a project should always have full privileges
     //on their project
            return true;
        } else {
            $arr = $this->getPermData();
            if (array_key_exists($field, $arr) && ($arr[$field] > $value)) {
                    return true;
            } else {
                    return false;
            }
        }
    }

        /**
         * This method relies on global state so kittens die everytime you use it
         *
         * @deprecated use PFUser::isAdmin() instead
         * @return bool
         */
    public function userIsAdmin()
    {
        if (isset($this->is_admin)) {
            //have already been through here and set the var
        } else {
            if (HTTPRequest::instance()->getCurrentUser()->isSuperUser()) {
                $this->is_admin = true;
            } else {
                if (user_isloggedin()) {
                    $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
                    //check to see if site super-user
                    $res = db_query(
                        "SELECT * FROM user_group WHERE user_id='" . $db_escaped_user_id . "' AND group_id='1' AND admin_flags='A'"
                    );
                    if ($res && db_numrows($res) > 0) {
                        $this->is_admin = true;
                    } else {
                        $arr = $this->getPermData();
                        if (array_key_exists('admin_flags', $arr) && $arr['admin_flags'] == 'A') {
                            $this->is_admin = true;
                        } else {
                            $this->is_admin = false;
                        }
                    }
                    db_free_result($res);
                } else {
                    $this->is_admin = false;
                }
            }
        }

        return $this->is_admin;
    }

    /*
        Return an associative array of permissions for this group/user
    */
    public function getPermData()
    {
        if ($this->perm_data_array) {
     //have already been through here and set up perms data
        } else {
            if (user_isloggedin()) {
                    $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
                    $res                = db_query("SELECT * FROM user_group WHERE user_id='" . $db_escaped_user_id . "' and group_id='" . db_ei($this->getGroupId()) . "'");
                if ($res && db_numrows($res) > 0) {
                    $this->perm_data_array = db_fetch_array($res);
                } else {
                    echo db_error();
                    $this->perm_data_array = [];
                }
                db_free_result($res);
            } else {
                    $this->perm_data_array = [];
            }
        }
        return $this->perm_data_array;
    }

    /**
     * Return true, if this group is a template to create other groups
     *
     * @return bool
     *
     * @psalm-mutation-free
     */
    public function isTemplate()
    {
        return TemplateSingleton::isTemplate($this->data_array['type']);
    }

    /** return the template id from which this group was built */
    public function getTemplate()
    {
        return $this->data_array['built_from_template'];
    }

    public function setType($type)
    {
        db_query("UPDATE `groups` SET type='$type' WHERE group_id='" . db_ei($this->group_id) . "'");
    }

    /**
     * Get template singleton
     *
     * @return TemplateSingleton
     */
    private function getTemplateSingleton()
    {
        return TemplateSingleton::instance();
    }

    /**
     * @param $string
     */
    public function setError($string)
    {
        $this->error_state   = true;
        $this->error_message = $string;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->error_state) {
            return $this->error_message;
        } else {
            return $GLOBALS['Language']->getText('include_common_error', 'no_err');
        }
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->error_state;
    }
}
