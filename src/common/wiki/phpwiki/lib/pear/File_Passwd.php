<?php
/* vim: set ts=4 sw=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Rasmus Lerdorf <rasmus@php.net>                              |
// +----------------------------------------------------------------------+
//
// $Id: File_Passwd.php,v 1.9 2004/06/03 18:06:29 rurban Exp $
//
// Manipulate standard UNIX passwd,.htpasswd and CVS pserver passwd files

require_once 'PEAR.php' ;

/**
* Class to manage passwd-style files
*
* @author Rasmus Lerdorf <rasmus@php.net>
*/
class File_Passwd {

    /**
    * Passwd file
    * @var string
    */
    var $filename ;

    /**
    * Hash list of users
    * @var array
    */
    var $users ;
    
    /**
    * hash list of csv-users
    * @var array
    */
    var $cvs ;
    
    /**
    * filehandle for lockfile
    * @var int
    */
    var $fplock ;
    
    /**
    * locking state
    * @var boolean
    */
    var $locked ;
    
    /**
    * name of the lockfile
    * @var string    
    */ 
    var $lockfile = './passwd.lock';

    /**
    * Constructor
    * Requires the name of the passwd file. This functions opens the file and read it.
    * Changes to this file will written first in the lock file, so it is still possible
    * to access the passwd file by another programs. The lock parameter controls the locking
    * oft the lockfile, not of the passwd file! ( Swapping $lock and $lockfile would
    * breaks bc to v1.3 and smaller).
    * Don't forget to call close() to save changes!
    * 
    * @param $file		name of the passwd file
    * @param $lock		if 'true' $lockfile will be locked
    * @param $lockfile	name of the temp file, where changes are saved
    *
    * @access public
    * @see close() 
    */

    function __construct($file, $lock = 0, $lockfile = "") {
        $this->filename = $file;
        if( !empty( $lockfile) ) {
            $this->lockfile = $lockfile;
        }

        if ($lock) {
            //check if already locked, on some error or race condition or other user.
            //FIXME: implement timeout as with dba
            if (!empty($this->lockfile) and file_exists($this->lockfile)) {
            	if (isset($_GET['force_unlock'])) {
            	    $this->fplock = fopen($this->lockfile, 'w');
            	    flock($this->fplock, LOCK_UN);
            	    fclose($this->fplock);
            	} else {
                    trigger_error('File_Passwd lock conflict: Try &force_unlock=1',E_USER_NOTICE);
            	}
            }
            $this->fplock = fopen($this->lockfile, 'w');
            flock($this->fplock, LOCK_EX);
            $this->locked = true;
        }
    
        $fp = fopen($file,'r') ;
        if( !$fp ) {
            return new PEAR_Error( "Couldn't open '$file'!", 1, PEAR_ERROR_RETURN) ;
        }
        while(!feof($fp)) {
            $line = fgets($fp, 128);
            $array = explode(':', $line);
            if (count($array) and strlen(trim($array[0]))) {
                $user = trim($array[0]);
                if (in_array(substr($user,0,1),array('#',';'))) continue;
                if (empty($array[1])) $array[1]='';
                $this->users[$user] = trim($array[1]);
                if (count($array) >= 3)
                    $this->cvs[$user] = trim($array[2]);	
            }
        }
        fclose($fp);
    }

    /**
    * Adds a user
    *
    * @param $user new user id
    * @param $pass password for new user
    * @param $cvs  cvs user id (needed for pserver passwd files)
    *
    * @return mixed returns PEAR_Error, if the user already exists
    * @access public
    */
    function addUser($user, $pass, $cvsuser = "") {
        if(!isset($this->users[$user]) && $this->locked) {
            $this->users[$user] = crypt($pass);
            $this->cvs[$user] = $cvsuser;
            return true;
        } else {
            return new PEAR_Error( "Couldn't add user '$user', because the user already exists!", 2, PEAR_ERROR_RETURN);
        }
    } // end func addUser()

    /**
    * Modifies a user
    *
    * @param $user user id
    * @param $pass new password for user
    * @param $cvs  cvs user id (needed for pserver passwd files)
    *
    * @return mixed returns PEAR_Error, if the user doesn't exists
    * @access public
    */

    function modUser($user, $pass, $cvsuser="") {
        if(isset($this->users[$user]) && $this->locked) {
            $this->users[$user] = crypt($pass);
            $this->cvs[$user] = $cvsuser;
            return true;
        } else {
            return new PEAR_Error( "Couldn't modify user '$user', because the user doesn't exists!", 3, PEAR_ERROR_RETURN) ;
        }
    } // end func modUser()

    /**
    * Deletes a user
    *
    * @param $user user id
    *
    * @return mixed returns PEAR_Error, if the user doesn't exists
    * @access public	
    */
    
    function delUser($user) {
        if (isset($this->users[$user]) && $this->locked) {
            unset($this->users[$user]);
            unset($this->cvs[$user]);
        } else {
            return new PEAR_Error( "Couldn't delete user '$user', because the user doesn't exists!", 3, PEAR_ERROR_RETURN) ; 
        }
    } // end func delUser()

    /**
    * Verifies a user's password
    *
    * @param $user user id
    * @param $pass password for user
    *
    * @return boolean true if password is ok
    * @access public		
    */
    function verifyPassword($user, $pass) {
        //if ($this->users[$user] == crypt($pass, substr($this->users[$user], 0, 2)))
        //  return true;
        if (isset($this->users[$user])) {
            $stored_password = $this->users[$user];
            if (function_exists('crypt')) {
                if (crypt($pass, $stored_password) == $stored_password)
                    return true; // matches encrypted password
                else
                    return false;
            } else {
                if ($pass == $stored_password)
                    return true; // matches plaintext password
                else {
                    trigger_error(_("The crypt function is not available in this version of PHP."),
                                  E_USER_WARNING);
                    return false;
                }
            }
        }
        return false;
    }

    /**
    * Return all users from passwd file
    *
    * @access public
    * @return array
    */
    function listUsers() {
        return $this->users;
    } // end func listUsers()

    /**
    * Writes changes to passwd file and unlocks it
    *
    * @access public
    */
    function close() {
        if ($this->locked) {
            foreach($this->users as $user => $pass) {
                if (isset($this->cvs[$user])) {
                    fputs($this->fplock, "$user:$pass:" . $this->cvs[$user] . "\n");
                } else {
                    fputs($this->fplock, "$user:$pass\n");
                }
            }
            @unlink($this->filename.'.bak');
            if (isWindows()) {
              // windows doesn't allow renaming of open files
              flock($this->fplock, LOCK_UN);
              $this->locked = false;
              fclose($this->fplock);
              rename($this->filename,$this->filename.'.bak');
              rename($this->lockfile, $this->filename);
            } else {
              rename($this->filename,$this->filename.'.bak');
              rename($this->lockfile, $this->filename);
              flock($this->fplock, LOCK_UN);
              $this->locked = false;
              fclose($this->fplock);
            }
            if (file_exists($this->filename))
                @unlink($this->filename.'.bak');
            else {
                rename($this->filename.'.bak',$this->filename);
            }
        }
    } // end func close()
}

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
