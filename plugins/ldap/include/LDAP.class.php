<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * LDAP class definition
 * Provides LDAP facilities:
 * - directory search
 * - user authentication
 * The ldap object is initialized with global parameters (from local.inc):
 * servers, query templates, etc.
 */
class LDAP {
    /**
     * This is equivalent to searching the entire directory. 
     */
    const SCOPE_SUBTREE      = 1;
    const SCOPE_SUBTREE_TEXT = 'subtree';
    
    /**
     * LDAP_SCOPE_ONELEVEL means that the search should only return information 
     * that is at the level immediately below the base_dn given in the call. 
     * (Equivalent to typing "ls" and getting a list of files and folders in 
     * the current working directory.)
     */
    const SCOPE_ONELEVEL      = 2;
    const SCOPE_ONELEVEL_TEXT = 'onelevel';
    
    /**
     * It is equivalent to reading an entry from the directory. 
     */
    const SCOPE_BASE     = 3;

    /**
     * Error value when search exceed either server or client size limit. 
     */
    const ERR_SIZELIMIT = 0x04 ;
    
    const ERR_SUCCESS   = 0x00;

    const SERVER_TYPE_ACTIVE_DIRECTORY = "ActiveDirectory";
    const SERVER_TYPE_OPEN_LDAP        = "OpenLDAP";

    private $ds;
    private $bound;
    private $errorsTrapped;
    private $ldapParams;
    private $query_escaper;

    /** @var Logger */
    private $logger;
    
    /**
     * LDAP object constructor. Use gloabals for initialization.
     */
    function __construct(array $ldapParams, Logger $logger, LdapQueryEscaper $query_escaper) {
        $this->ldapParams    =  $ldapParams;
        $this->bound         = false;
        $this->errorsTrapped = true;
        $this->logger        = $logger;
        $this->query_escaper = $query_escaper;
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
            foreach (explode('[,;]', $this->ldapParams['server']) as $ldap_server) {
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
                        $this->logger->debug('Bound to LDAP server: '.$ldap_server);
                        return true;
                    } else {
                        $this->logger->warn('Cannot bind to LDAP server: '.$ldap_server.
                            ' ***ERROR MESSSAGE:'. ldap_error($this->ds).
                            ' ***ERROR no:'. $this->getErrno()
                        );
                    }
                } else {
                    $this->logger->warn('Cannot connect to LDAP server: '.$ldap_server.
                        ' ***ERROR:'. ldap_error($this->ds) .
                        ' ***ERROR no:'. $this->getErrno()
                    );
                }
            }
            $this->logger->warn('Cannot connect to any LDAP server: '.$this->ldapParams['server'].
                ' ***ERROR:'. ldap_error($this->ds).
                ' ***ERROR no:'. $this->getErrno()
            );
            return false;
        } else {
            return true;
        }
    }

    private function authenticatedBindConnect($servers, $binddn, $bindpwd) {
        $ds = false;
        foreach (preg_split('/[,;]/', $servers) as $ldap_server) {
            $ds = ldap_connect($ldap_server);
            if ($ds) {
                // Force protocol to LDAPv3 (for AD & recent version of OpenLDAP)
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

                // Since ldap_connect always return a resource with
                // OpenLdap 2.2.x, we have to check that this ressource is
                // valid with a bind, If bind success: that's great, if
                // not, this is a connexion failure.
                if (@ldap_bind($ds, $binddn, $bindpwd)) {
                    return $ds;
                } else {
                    throw new LDAP_Exception_BindException(ldap_error($ds));
                }
            }
        }
        throw new LDAP_Exception_ConnexionException(ldap_error($ds));
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
                $this->logger->error('Cannot connect to LDAP server: '.$this->ldapParams['server'].
                    ' ***ERROR: will not bind if a username is given and the server accepts anonymous connections'
                );
                $this->bound = false;
            }

            if ($bind_result = @ldap_bind($this->ds, $binddn, $bindpw)) {
                $this->bound = true;
            } else {
                $this->logger->error('Unable to bind to LDAP server: '.$this->ldapParams['server'].
                    ' ***ERROR:'. ldap_error($this->ds) .
                    ' ***ERROR no:'. $this->getErrno()
                );
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
    public function search($baseDn, $filter, $scope=self::SCOPE_SUBTREE, $attributes=array(), $attrsOnly=0, $sizeLimit=0, $timeLimit=0, $deref=LDAP_DEREF_NEVER) {
        $this->trapErrors();

        if ($this->_connectAndBind()) {
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
                $this->logger->debug('LDAP search success '.$baseDn.' '.$filter. ' *** SCOPE: '.$scope . ' *** ATTRIBUTES: '.implode(', ', $attributes));
                $entries = ldap_get_entries($this->ds, $sr);
                if ($entries !== false) {
                    return new LDAPResultIterator($entries, $this->ldapParams);
                }
            } else {
                $this->logger->warn('LDAP search error: '.$baseDn.' '.$filter.' '.$this->ldapParams['server'].
                    ' ***ERROR:'. ldap_error($this->ds).
                    ' ***ERROR no:'. $this->getErrno()
                );
            }
        }

        return false;
    }

    public function getDefaultAttributes() {
        return array(
            $this->ldapParams['mail'],
            $this->ldapParams['cn'],
            $this->ldapParams['uid'],
            $this->ldapParams['eduid'],
            'dn'
        );
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
        $attributes = count($attributes) > 0 ? $attributes : $this->getDefaultAttributes();
        return $this->search($dn, 'objectClass=*', self::SCOPE_BASE, $attributes);
    }

    /**
     * Search if given argument correspond to a LDAP login (generally this
     * correspond to ldap 'uid' field).
     *
     * @param String $name login
     * @param array $attributes
     *
     * @return LDAPResultIterator
     */    
    public function searchLogin($name, $attributes = array()) {
        $name = $this->query_escaper->escapeFilter($name);
        if (! $attributes) {
            $attributes = $this->getDefaultAttributes();
        }

        $filter = $this->ldapParams['uid'].'='.$name;
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $attributes);
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
        $name = $this->query_escaper->escapeFilter($name);
        $filter = $this->ldapParams['eduid'].'='.$name;
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $this->getDefaultAttributes());
    }

    /**
     * Search if a LDAP user match a filter defined in local conf.
     *
     * @param String $words User name to search
     * 
     * @return LDAPResultIterator
     */
    function searchUser($words) {
        $words = $this->query_escaper->escapeFilter($words);
        $filter = str_replace("%words%", $words, $this->ldapParams['search_user']);
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $this->getDefaultAttributes());
    }

    /**
     * Search if given identifier match a Common Name in the LDAP.
     *
     * @param String $name Common name to search
     * 
     * @return LDAPResultIterator
     */
    function searchCommonName($name) {
        $name = $this->query_escaper->escapeFilter($name);
        $filter = $this->ldapParams['cn'].'='.$name;
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $this->getDefaultAttributes());
    }

    /**
     * Search ldap group by name
     *
     * @param String $name Group name to search
     * 
     * @return LDAPResultIterator
     */
    function searchGroup($name) {
        $name = $this->query_escaper->escapeFilter($name);

        if (isset($this->ldapParams['server_type']) && $this->ldapParams['server_type'] === self::SERVER_TYPE_ACTIVE_DIRECTORY) {
            $filter = $this->ldapParams['grp_uid'] . '=' . $name;
            return $this->search($this->ldapParams['grp_dn'], $filter, self::SCOPE_SUBTREE);
        }

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
            $name = $this->query_escaper->escapeFilter($name);
            if (isset($this->ldapParams['tooltip_search_user'])) {
                $filter = str_replace("%words%", $name, $this->ldapParams['tooltip_search_user']);
            } else {
                $filter = '('.$this->ldapParams['cn'].'='.$name.'*)';
            }
            if($validEmail) {
                // Only search people with a non empty mail field
                $mail = $this->query_escaper->escapeFilter($this->ldapParams['mail']);
                $filter = '(&'.$filter.'('.$mail.'=*))';
            }
            // We only care about Common name and Login (lower the amount of data
            // to fetch speed up the request.
            if (isset($this->ldapParams['tooltip_search_attrs'])) {
                $attrs = explode(';', $this->ldapParams['tooltip_search_attrs']);
            } else {
                $attrs  = array($this->ldapParams['cn'], $this->ldapParams['uid']);
            }

            if (! in_array($this->ldapParams['eduid'], $attrs)) {
                $attrs[] = $this->ldapParams['eduid'];
            }

            // We want types and values
            $attrsOnly = 0;
            // Catch errors to detect if there are more results available than
            // the list actually returned (helps to refine the search)
            $this->trapErrors();
            // Use SCOPE_ONELEVEL to only search in "sys_ldap_people_dn" branch 
            // of the directory to speed up the search.
            $peopleDn = explode(';', $this->ldapParams['people_dn']);
            foreach ($peopleDn as $count) {
                $ds[] = $this->ds;
            }
            if (isset($this->ldapParams['tooltip_search_user'])) {
                $asr = ldap_search($ds, $peopleDn, $filter, $attrs, $attrsOnly, $sizeLimit, 0, LDAP_DEREF_NEVER);
                $this->logger->debug('LDAP in-depth search as you type '.$filter. ' *** PEOPLEDN: '.implode(',', $peopleDn) . ' *** errors:'.  ldap_error($this->ds));
            } else {
                $asr = ldap_list($ds, $peopleDn, $filter, $attrs, $attrsOnly, $sizeLimit, 0, LDAP_DEREF_NEVER);
                $this->logger->debug('LDAP high-level search as you type '.$filter. ' *** PEOPLEDN: '.implode(',', $peopleDn) . ' *** errors:'.  ldap_error($this->ds));
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
            $name = $this->query_escaper->escapeFilter($name);
            $filter = '('.$this->ldapParams['grp_cn'].'=*'.$name.'*)';
            // We only care about Common name
            $attrs  = array($this->ldapParams['grp_cn']);
            // We want types and values
            $attrsOnly = 0; 
            // Catch errors to detect if there are more results available than
            // the list actually returned (helps to refine the search)
            $this->trapErrors();
            $lri = $this->search($this->ldapParams['grp_dn'], $filter, $this->getSearchGroupScope(), $attrs, $attrsOnly, $sizeLimit);
        }
        if ($lri === false) {
            return new LDAPResultIterator(array(), array());
        } else {
            return $lri;
        }
    }

    private function getSearchGroupScope()
    {
        $scope = '';
        if (isset($this->ldapParams['grp_search_scope'])) {
            $scope = $this->ldapParams['grp_search_scope'];
        }
        return $this->getScopeFromConf($scope);
    }

    private function getScopeFromConf($scope)
    {
        switch (strtolower($scope)) {
            case self::SCOPE_SUBTREE_TEXT:
                return self::SCOPE_SUBTREE;

            case self::SCOPE_ONELEVEL_TEXT:
            default:
                return self::SCOPE_ONELEVEL;
        }
    }

    public function add($dn, array $info) {
        $ds = $this->getWriteConnexion();
        if (@ldap_add($ds, $dn, $info)) {
            return true;
        }
        throw new LDAP_Exception_AddException(ldap_error($ds), $dn);
    }

    public function update($dn, array $info) {
        $ds = $this->getWriteConnexion();
        if (@ldap_modify($ds, $dn, $info)) {
            return true;
        }
        throw new LDAP_Exception_UpdateException(ldap_error($ds), $dn);
    }

    public function delete($dn) {
        $ds = $this->getWriteConnexion();
        if (@ldap_delete($ds, $dn)) {
            return true;
        }
        throw new LDAP_Exception_DeleteException(ldap_error($ds), $dn);
    }

    public function renameUser($old_dn, $new_root_dn) {
        return $this->rename($old_dn, $new_root_dn, $this->getLDAPParam('write_people_dn'));
    }

    private function rename($old_dn, $newrdn, $newparent) {
        $ds = $this->getWriteConnexion();
        if (@ldap_rename($ds, $old_dn, $newrdn, $newparent, true)) {
            return true;
        }
        throw new LDAP_Exception_RenameException(ldap_error($ds), $old_dn, $newrdn.','.$newparent);
    }

    private function getWriteConnexion() {
        return $this->authenticatedBindConnect(
            $this->getLDAPParam('write_server'),
            $this->getLDAPParam('write_dn'),
            $this->getLDAPParam('write_password')
        );
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
