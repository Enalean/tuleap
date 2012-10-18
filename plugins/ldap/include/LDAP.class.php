<?php
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com

require_once 'LDAPResult.class.php';

/**
 * LDAP class definition
 * Provides LDAP facilities to Codendi:
 * - directory search
 * - user authentication
 * The ldap object is initialized with global parameters (from local.inc):
 * servers, query templates, etc.
 */
class LDAP {
    /**
     * This is equivalent to searching the entire directory. 
     */
    const SCOPE_SUBTREE  = 1;
    
    /**
     * LDAP_SCOPE_ONELEVEL means that the search should only return information 
     * that is at the level immediately below the base_dn given in the call. 
     * (Equivalent to typing "ls" and getting a list of files and folders in 
     * the current working directory.)
     */
    const SCOPE_ONELEVEL = 2;
    
    /**
     * It is equivalent to reading an entry from the directory. 
     */
    const SCOPE_BASE     = 3;

    /**
     * Error value when search exceed either server or client size limit. 
     */
    const ERR_SIZELIMIT = 0x04 ;
    
    const ERR_SUCCESS   = 0x00;
    
    private $ds;
    private $bound;
    private $errorsTrapped;
    private $ldapParams;
    
    /**
     * LDAP object constructor. Use gloabals for initialization.
     */
    function __construct(array $ldapParams) {
        $this->ldapParams    =  $ldapParams;
        $this->bound         = false;
        $this->errorsTrapped = true;
    }
    
    /**
     * Returns the whole LDAP parameters set by admin
     * 
     * @return array
     */
    function getLDAPParams() {
        return $this->ldapParams;
    }

    /**
     * Returns one parameter from the list set by admin
     * 
     * @param String $key Parameter name
     * 
     * @return String
     */
    function getLDAPParam($key) {
        return isset($this->ldapParams[$key]) ?  $this->ldapParams[$key] : null;
    }
    
    /**
     * Connect to LDAP server.
     * If several servers are listed, try first server first, then second, etc.
     * This funtion should not be called directly: it is always called
     * by a public function: authenticate() or search().
     * 
     * @return Boolean true if connect was successful, false otherwise.
     */ 
    function connect() {
        if (!$this->ds) {
            foreach (split('[,;]', $this->ldapParams['server']) as $ldap_server) {  	    
                $this->ds = ldap_connect($ldap_server);
                if($this->ds) {
                    // Force protocol to LDAPv3 (for AD & recent version of OpenLDAP)
                    ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                    ldap_set_option($this->ds, LDAP_OPT_REFERRALS, 0);

                    // Since ldap_connect always return a resource with
                    // OpenLdap 2.2.x, we have to check that this ressource is
                    // valid with a bind, If bind success: that's great, if
                    // not, this is a connexion failure.
                    if($this->bind()) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Perform LDAP binding.
     * - Some servers allow anonymous bindings for searching. Otherwise, set
     *  sys_ldap_bind_dn and sys_ldap_bind_passwd in local.inc
     * - binding is also used for user authentication. A successful bind
     *   means that the user/password is valid.
     *
     * @param String $binddn DN to use to bind with
     * @param String $bindpw Password associated to the DN
     * 
     * @return Boolean true if bind was successful, false otherwise.
     */
    function bind($binddn=null, $bindpw=null) {
        if(!$this->bound) {
            if (!$binddn) {
                $binddn=isset($this->ldapParams['bind_dn']) ? $this->ldapParams['bind_dn'] : null;
                $bindpw=isset($this->ldapParams['bind_passwd']) ? $this->ldapParams['bind_passwd'] : null;
            }
            if ($binddn && (!$bindpw)) {
                // Prevent successful binding if a username is given and the server
                // accepts anonymous connections
                //$this->setError($Language->getText('ldap_class','err_bind_nopasswd',$binddn));
                $this->bound = false;
            }

            if ($bind_result = @ldap_bind($this->ds, $binddn, $bindpw)) {
                $this->bound = true;
            } else {
                //$this->setError($Language->getText('ldap_class','err_bind_invpasswd',$binddn));
                $this->bound = false;
            }
        }
        return $this->bound;
    }

    /**
     * Unbinds from the LDAP directory
     * 
     * According to http://www.php.net/manual/en/function.ldap-unbind.php#17203
     * ldap_unbind kills the link descriptor so we just have to force the rebind
     * for next query
     */
    function unbind() {
        $this->bound = false;
    }
    
    /**
     * Connect and bind to the LDAP Directory
     *
     * @return Boolean
     */
    function _connectAndBind() {
        if (!$this->connect()) {
            //$this->setError($Language->getText('ldap_class','err_cant_connect'));
            return false;
        }
        if (!$this->bind()) {
            //$this->setError($Language->getText('ldap_class','err_cant_bind'));
            return false;
        }
        return true;
    }
    
    /**
     * Escape String before passing them to LDAP server.
     *
     * From: http://www.php.net/manual/en/function.ldap-search.php#83774
     * Escape string
     * see: RFC2254
     * 
     * @param String $str String to escape
     *  
     * @todo see escaping defined in pear package (http://pear.php.net/package/Net_LDAP2/)
     * 
     * @return String
     */
    function escapeString($str){
        $metaChars = array('\\', '(', ')', '#', '*');
        $quotedMetaChars = array();
        // Convert the meta chars in their hexadecimal value.
        foreach ($metaChars as $key => $value) $quotedMetaChars[$key] = '\\'.dechex(ord($value));
        $str=str_replace($metaChars,$quotedMetaChars,$str); //replace them
        return ($str);
    }

    /**
     * Return last error state
     * 
     * @return Integer
     */
    function getErrno() {
        return ldap_errno($this->ds);
    }    
    
    /** 
     * Perform LDAP authentication of a user based on its login.
     * 
     * First search the DN of the user based on its login then try to bind
     * with this DN and the given password
     *
     * @param String $login  Login name to authenticate with
     * @param String $passwd Password associated to the login
     * 
     * @return Boolean true if the login and password match, false otherwise
     */
    function authenticate($login, $passwd) {
        if (!$passwd) {
            // avoid a successful bind on LDAP servers accepting anonymous connections
            //$this->setError($Language->getText('ldap_class','err_nopasswd'));
            return false;
        }

        // Do a search to recover the right DN based on given login
        $lri = $this->searchLogin($login);
        if ($lri && count($lri) === 1) {
            $auth_dn = $lri->current()->getDn();
        } else {
            return false;
        }

        // Now bind with DN/password to check authentication
        // /!\ Be sure not to reuse a previously bound connexion (otherwise
        // authentication will always be successfull.
        $this->unbind();
        if (!$this->bind($auth_dn,$passwd)) {
            //$this->setError($Language->getText('ldap_class','err_badpasswd'));
            return false;
        }
        return true;
    }
    
    /**
     * Search in the LDAP directory
     * 
     * @see http://php.net/ldap_search
     * 
     * @param String  $baseDn     Base DN where to search
     * @param String  $filter     Specific LDAP query
     * @param Integer $scope      How to search (SCOPE_ SUBTREE, ONELEVEL or BASE)
     * @param Array   $attributes LDAP fields to retreive
     * @param Integer $attrsOnly  Retreive both field value and name (keep it to 0)
     * @param Integer $sizeLimit  Limit the size of the result set
     * @param Integer $timeLimit  Limit the time spend to search for results
     * @param Integer $deref      Dereference result
     * 
     * @return LDAPResultIterator
     */
    function search($baseDn, $filter, $scope=self::SCOPE_SUBTREE, $attributes=array(), $attrsOnly=0, $sizeLimit=0, $timeLimit=0, $deref=LDAP_DEREF_NEVER) {
        if($this->_connectAndBind()) {
            $this->_initErrorHandler();
            switch ($scope) {
            case self::SCOPE_BASE:
                $sr = ldap_read($this->ds, $baseDn, $filter, $attributes, $attrsOnly, $sizeLimit, $timeLimit, $deref);
                break;
            
            case self::SCOPE_ONELEVEL:
                $sr = ldap_list($this->ds, $baseDn, $filter, $attributes, $attrsOnly, $sizeLimit, $timeLimit, $deref);
                break;
            
            case self::SCOPE_SUBTREE:
            default:
                $sr = ldap_search($this->ds, $baseDn, $filter, $attributes, $attrsOnly, $sizeLimit, $timeLimit, $deref);
            }
            $this->_restoreErrorHandler();

            if ($sr !== false) {
                $entries = ldap_get_entries($this->ds, $sr);
                if ($entries !== false) {
                    return new LDAPResultIterator($entries, $this->ldapParams);
                }
            }
        }
        return false;
    }

    /**
     * Search a specific Distinguish Name
     *
     * @param String $dn         DN to retreive
     * @param Array  $attributes Restrict the LDAP fields to fetch
     * 
     * @return LDAPResultIterator
     */
    function searchDn($dn, $attributes=array()) {
        return $this->search($dn, 'objectClass=*', self::SCOPE_BASE, $attributes);
    }

    /**
     * Search if given argument correspond to a LDAP login (generally this
     * correspond to ldap 'uid' field).
     *
     * @param String $name login
     * 
     * @return LDAPResultIterator
     */    
    function searchLogin($name) {
        $filter = $this->ldapParams['uid'].'='.$name;
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE);
    }

    
    /**
     * Search if given argument correspond to a LDAP Identifier. This is the
     * uniq number that represent a user.
     *
     * @param String $name LDAP Id
     * 
     * @return LDAPResultIterator
     */  
    function searchEdUid($name) {
        $filter = $this->ldapParams['eduid'].'='.$name;
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE);
    }


    /**
     * Search if a LDAP user match a filter defined in local conf.
     *
     * @param String $words User name to search
     * 
     * @return LDAPResultIterator
     */
    function searchUser($words) {
        $filter = str_replace("%words%", $words, $this->ldapParams['search_user']);
        $attributes = array($this->getLDAPParam('cn'),
                            $this->getLDAPParam('eduid'),
                            $this->getLDAPParam('mail'),
                            $this->getLDAPParam('uid'));
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $attributes);
    }

    /**
     * Search if given identifier match a Common Name in the LDAP.
     *
     * @param String $name Common name to search
     * 
     * @return LDAPResultIterator
     */
    function searchCommonName($name) {
        $filter = $this->ldapParams['cn'].'='.$name;
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE);
    }

    /**
     * Search ldap group by name
     *
     * @param String $name Group name to search
     * 
     * @return LDAPResultIterator
     */
    function searchGroup($name) {
        $filter = $this->ldapParams['grp_cn'].'='.$name;
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE);
    }
    
    /**
     * List members of a LDAP group
     * 
     * @param String $groupDn Group DN
     * 
     * @return LDAPResultIterator
     */
    function searchGroupMembers($groupDn) {
        return $this->search($groupDn, 'objectClass=*', self::SCOPE_SUBTREE, array($this->ldapParams['grp_member']));
    }

    /**
     * Specific search of user common name, only the common name is returned
     *
     * This method is designed for speed and to limit the number of returned values.
     * 
     * @param String   $name      Name of the group to look for
     * @param Integer  $sizeLimit Limit the amount of result sent
     * 
     * @return LDAPResultIterator
     */
    function searchUserAsYouType($name, $sizeLimit, $validEmail=false) {
        $apIt  = new AppendIterator();
        if($name && $this->_connectAndBind()) {
            if (isset($this->ldapParams['tooltip_search_user'])) {
                $filter = str_replace("%words%", $name, $this->ldapParams['tooltip_search_user']);
            } else {
                $filter = '('.$this->ldapParams['cn'].'='.$name.'*)';
            }
            if($validEmail) {
                // Only search people with a non empty mail field
                $filter = '(&'.$filter.'('.$this->ldapParams['mail'].'=*))';
            }
            // We only care about Common name and Login (lower the amount of data
            // to fetch speed up the request.
            if (isset($this->ldapParams['tooltip_search_attrs'])) {
                $attrs = explode(';', $this->ldapParams['tooltip_search_attrs']);
            } else {
                $attrs  = array($this->ldapParams['cn'], $this->ldapParams['uid']);
            }
            // We want types and values
            $attrsOnly = 0;
            // Catch errors to detect if there are more results available than
            // the list actually returned (helps to refine the search)
            $this->trapErrors();
            // Use SCOPE_ONELEVEL to only search in "sys_ldap_people_dn" branch 
            // of the directory to speed up the search.
            $peopleDn = split(';', $this->ldapParams['people_dn']);
            foreach ($peopleDn as $count) {
                $ds[] = $this->ds;
            }
            if (isset($this->ldapParams['tooltip_search_user'])) {
                $asr = ldap_search($ds, $peopleDn, $filter, $attrs, $attrsOnly, $sizeLimit, 0, LDAP_DEREF_NEVER);
            } else {
                $asr = ldap_list($ds, $peopleDn, $filter, $attrs, $attrsOnly, $sizeLimit, 0, LDAP_DEREF_NEVER);
            }
            if ($asr !== false) {
                foreach ($asr as $sr) {
                    $entries = ldap_get_entries($this->ds, $sr);
                    if ($entries !== false) {
                        // AppendIterator doesn't seem to handle invalid iterator well.
                        // So don't append invalid iterators...
                        $it = new LDAPResultIterator($entries, $this->ldapParams);
                        if ($it->valid()) {
                            $apIt->append($it);
                        }
                    }
                }
            }
        }
        return $apIt;
    }

    /**
     * Specific search of group common name, only the common name is returned
     *
     * This method is designed for speed and to limit the number of returned values.
     * 
     * @param String   $name      Name of the group to look for
     * @param Integer $sizeLimit Limit the amount of result sent
     * 
     * @return LDAPResultIterator
     */
    function searchGroupAsYouType($name, $sizeLimit) {
        $lri = false;
        if($this->_connectAndBind()) {
            $filter = '('.$this->ldapParams['grp_cn'].'=*'.$name.'*)';
            // We only care about Common name
            $attrs  = array($this->ldapParams['grp_cn']);
            // We want types and values
            $attrsOnly = 0; 
            // Catch errors to detect if there are more results available than
            // the list actually returned (helps to refine the search)
            $this->trapErrors();
            // Use SCOPE_ONELEVEL to only search in "sys_ldap_grp_dn" branch 
            // of the directory to speed up the search.
            $lri = $this->search($this->ldapParams['grp_dn'], $filter, self::SCOPE_ONELEVEL, $attrs, $attrsOnly, $sizeLimit);
        }
        if ($lri === false) {
            return new LDAPResultIterator(array(), array());
        } else {
            return $lri;
        }
    }
    
    /**
     * Enable fake error handler
     * 
     * The fake error handler is enabled only for one query.
     * 
     * @see _initErrorHandler()
     */
    private function trapErrors() {
        $this->errorsTrapped = true;
    }
    
    /**
     * Setup fake error handler to be able to catch an error without displaying it
     *
     * This is not very clean but it's the only way to get some ldap errors
     * without displaying them to final users. In some cases errors are meaningful
     * and even expected (see searchAsYouType*) because we set very restrictive
     * limits and of course the limit is exceeded easily. We need to catch it
     * but not to display a warning to the user.
     * 
     * Note: don't enable it for each request, otherwise, you may hide unwanted
     * errors.
     */
    private function _initErrorHandler() {
        if ($this->errorsTrapped) {
            set_error_handler(create_function('',''));
        }
    }

    /**
     * After LDAP query, restore the PHP error handler to its previous state.
     */
    private function _restoreErrorHandler() {
        if ($this->errorsTrapped) {
            restore_error_handler();
        }
        $this->errorsTrapped = false;
    }
}

?>
