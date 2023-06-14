<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Cryptography\ConcealedString;

/**
 * LDAP class definition
 * Provides LDAP facilities:
 * - directory search
 * - user authentication
 * The ldap object is initialized with global parameters (from local.inc):
 * servers, query templates, etc.
 */
class LDAP
{
    public const CONFIGURATION_VARIABLES = [
        'sys_ldap_server_common_name',
        'sys_ldap_server',
        'sys_ldap_server_type',
        'sys_ldap_dn',
        'sys_ldap_bind_dn',
        'sys_ldap_bind_passwd',
        'sys_ldap_uid',
        'sys_ldap_eduid',
        'sys_ldap_cn',
        'sys_ldap_mail',
        'sys_ldap_people_dn',
        'sys_ldap_search_user',
        'sys_ldap_tooltip_search_user',
        'sys_ldap_tooltip_search_attrs',
        'sys_ldap_default_user_status',
        'sys_ldap_svn_auth',
        'sys_ldap_daily_sync',
        'sys_ldap_daily_sync_retention_period',
        'sys_ldap_user_management',
        'sys_ldap_grp_enabled',
        'sys_ldap_grp_sync_admin_notifications_enabled',
        'sys_ldap_grp_dn',
        'sys_ldap_grp_search_scope',
        'sys_ldap_grp_cn',
        'sys_ldap_grp_member',
        'sys_ldap_grp_uid',
        'sys_ldap_grp_oc',
        'sys_ldap_user_oc',
        'sys_ldap_grp_display_name',
        'sys_ldap_tooltip_search_grp',
        'sys_ldap_threshold_users_suspension',
        'search_depth',
    ];

    /**
     * This is equivalent to searching the entire directory.
     */
    public const SCOPE_SUBTREE      = 1;
    public const SCOPE_SUBTREE_TEXT = 'subtree';

    /**
     * LDAP_SCOPE_ONELEVEL means that the search should only return information
     * that is at the level immediately below the base_dn given in the call.
     * (Equivalent to typing "ls" and getting a list of files and folders in
     * the current working directory.)
     */
    public const SCOPE_ONELEVEL      = 2;
    public const SCOPE_ONELEVEL_TEXT = 'onelevel';

    /**
     * It is equivalent to reading an entry from the directory.
     */
    public const SCOPE_BASE = 3;

    /**
     * Error value when search exceed either server or client size limit.
     */
    public const ERR_SIZELIMIT = 0x04;

    public const ERR_SUCCESS = 0x00;

    public const SERVER_TYPE_ACTIVE_DIRECTORY = "ActiveDirectory";
    public const SERVER_TYPE_OPEN_LDAP        = "OpenLDAP";

    private $ds;
    private $bound;
    private $errorsTrapped;
    private $ldapParams;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * LDAP object constructor. Use gloabals for initialization.
     */
    public function __construct(array $ldapParams, \Psr\Log\LoggerInterface $logger)
    {
        $this->ldapParams    =  $ldapParams;
        $this->bound         = false;
        $this->errorsTrapped = true;
        $this->logger        = $logger;
    }

    /**
     * Returns the whole LDAP parameters set by admin
     *
     * @return array
     */
    public function getLDAPParams()
    {
        return $this->ldapParams;
    }

    /**
     * Returns one parameter from the list set by admin
     *
     * @param String $key Parameter name
     *
     * @return String|null
     */
    public function getLDAPParam($key)
    {
        return isset($this->ldapParams[$key]) ?  $this->ldapParams[$key] : null;
    }

    /**
     * Connect to LDAP server.
     * If several servers are listed, try first server first, then second, etc.
     * This funtion should not be called directly: it is always called
     * by a public function: authenticate() or search().
     *
     * @return bool true if connect was successful, false otherwise.
     */
    public function connect()
    {
        if (! $this->ds) {
            foreach (preg_split('/[,;]/', $this->ldapParams['server']) as $ldap_server) {
                $this->ds = ldap_connect($ldap_server);
                if ($this->ds) {
                    // Force protocol to LDAPv3 (for AD & recent version of OpenLDAP)
                    ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                    ldap_set_option($this->ds, LDAP_OPT_REFERRALS, 0);

                    // Since ldap_connect always return a resource with
                    // OpenLdap 2.2.x, we have to check that this ressource is
                    // valid with a bind, If bind success: that's great, if
                    // not, this is a connexion failure.
                    if ($this->bind()) {
                        $this->logger->debug('Bound to LDAP server: ' . $ldap_server);
                        return true;
                    } else {
                        $this->logger->warning('Cannot bind to LDAP server: ' . $ldap_server .
                            ' ***ERROR MESSSAGE:' . ldap_error($this->ds) .
                            ' ***ERROR no:' . $this->getErrno());
                    }
                } else {
                    $this->logger->warning('Cannot connect to LDAP server: ' . $ldap_server .
                        ' ***ERROR:' . ldap_error($this->ds) .
                        ' ***ERROR no:' . $this->getErrno());
                }
            }
            $this->logger->warning('Cannot connect to any LDAP server: ' . $this->ldapParams['server'] .
                ' ***ERROR:' . ldap_error($this->ds) .
                ' ***ERROR no:' . $this->getErrno());
            return false;
        } else {
            return true;
        }
    }

    private function authenticatedBindConnect($servers, $binddn, $bindpwd)
    {
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
     * @param ConcealedString $bindpw Password associated to the DN
     *
     * @return bool true if bind was successful, false otherwise.
     */
    public function bind($binddn = null, ?ConcealedString $bindpw = null)
    {
        if (! $this->bound) {
            if (! $binddn) {
                $binddn = isset($this->ldapParams['bind_dn']) ? $this->ldapParams['bind_dn'] : null;
                $bindpw = isset($this->ldapParams['bind_passwd']) ? new ConcealedString((string) $this->ldapParams['bind_passwd']) : null;
            }
            if ($binddn && (! $bindpw)) {
                // Prevent successful binding if a username is given and the server
                // accepts anonymous connections
                $this->logger->error('Cannot connect to LDAP server: ' . $this->ldapParams['server'] .
                    ' ***ERROR: will not bind if a username is given and the server accepts anonymous connections');
                $this->bound = false;
            }

            if ($bind_result = @ldap_bind($this->ds, $binddn, $bindpw === null ? null : $bindpw->getString())) {
                $this->bound = true;
            } else {
                $error_message = 'Unable to bind to LDAP server: ' . $this->ldapParams['server'] .
                    ' ***Bind DN:' . ($binddn ?? '<NULL>') .
                    ' ***ERROR:' . ldap_error($this->ds) .
                    ' ***ERROR no:' . $this->getErrno();
                if (ldap_get_option($this->ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
                    $error_message .= ' ***ERROR extended: ' . print_r($extended_error, true);
                }
                $this->logger->error($error_message);
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
    public function unbind()
    {
        $this->bound = false;
    }

    /**
     * Connect and bind to the LDAP Directory
     *
     * @return bool
     */
    public function _connectAndBind()
    {
        if (! $this->connect()) {
            return false;
        }
        if (! $this->bind()) {
            return false;
        }
        return true;
    }

    /**
     * Return last error state
     *
     * @return int
     */
    public function getErrno()
    {
        return ldap_errno($this->ds);
    }

    /**
     * Perform LDAP authentication of a user based on its login.
     *
     * First search the DN of the user based on its login then try to bind
     * with this DN and the given password
     *
     * @param string $login  Login name to authenticate with
     *
     * @return bool true if the login and password match, false otherwise
     */
    public function authenticate($login, ConcealedString $passwd)
    {
        if ($passwd->isIdenticalTo(new ConcealedString(''))) {
            // avoid a successful bind on LDAP servers accepting anonymous connections
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
        try {
            return $this->bind($auth_dn, $passwd);
        } finally {
            $this->unbind();
        }
    }

    /**
     * Search in the LDAP directory
     *
     * @see http://php.net/ldap_search
     *
     * @param string  $baseDn     Base DN where to search
     * @param string  $filter     Specific LDAP query
     * @param int $scope How to search (SCOPE_ SUBTREE, ONELEVEL or BASE)
     * @param array   $attributes LDAP fields to retreive
     * @param int $attrsOnly Retreive both field value and name (keep it to 0)
     * @param int $sizeLimit Limit the size of the result set
     * @param int $timeLimit Limit the time spend to search for results
     * @param int $deref Dereference result
     *
     * @return LDAPResultIterator|false
     */
    public function search($baseDn, $filter, $scope = self::SCOPE_SUBTREE, $attributes = [], $attrsOnly = 0, $sizeLimit = 0, $timeLimit = 0, $deref = LDAP_DEREF_NEVER)
    {
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
                $this->logger->debug('LDAP search success ' . $baseDn . ' ' . $filter . ' *** SCOPE: ' . $scope . ' *** ATTRIBUTES: ' . implode(', ', $attributes));
                /** @psalm-var \LDAP\Result $sr */
                $entries = ldap_get_entries($this->ds, $sr);
                if ($entries !== false) {
                    return new LDAPResultIterator($entries, $this->ldapParams);
                }
            } else {
                $exception = new RuntimeException('LDAP search error');
                $this->logger->warning(
                    'LDAP search error: ' . $baseDn . ' ' . $filter . ' ' . $this->ldapParams['server'] .
                    ' ***ERROR:' . ldap_error($this->ds) .
                    ' ***ERROR no:' . $this->getErrno(),
                    ['exception' => $exception]
                );
            }
        }

        return false;
    }

    public function getDefaultAttributes()
    {
        return [
            $this->ldapParams['mail'],
            $this->ldapParams['cn'],
            $this->ldapParams['uid'],
            $this->ldapParams['eduid'],
            'dn',
        ];
    }

    /**
     * Search a specific Distinguish Name
     *
     * @param string $dn         DN to retreive
     * @param array  $attributes Restrict the LDAP fields to fetch
     *
     * @return LDAPResultIterator|false
     */
    public function searchDn($dn, $attributes = [])
    {
        $attributes = count($attributes) > 0 ? $attributes : $this->getDefaultAttributes();
        return $this->search($dn, 'objectClass=*', self::SCOPE_BASE, $attributes);
    }

    /**
     * Search if given argument correspond to a LDAP login (generally this
     * correspond to ldap 'uid' field).
     *
     * @param string $name login
     * @param array $attributes
     *
     * @return LDAPResultIterator|false
     */
    public function searchLogin($name, $attributes = [])
    {
        if (! $attributes) {
            $attributes = $this->getDefaultAttributes();
        }

        $filter = $this->ldapParams['uid'] . '=' . ldap_escape($name, '', LDAP_ESCAPE_FILTER);
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $attributes);
    }

    /**
     * Search if given argument correspond to a LDAP Identifier. This is the
     * uniq number that represent a user.
     *
     * @param string $name LDAP Id
     *
     * @return LDAPResultIterator|false
     */
    public function searchEdUid($name)
    {
        $filter = $this->ldapParams['eduid'] . '=' . ldap_escape($name, '', LDAP_ESCAPE_FILTER);
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $this->getDefaultAttributes());
    }

    public function searchEmail(string $email): LDAPResultIterator|false
    {
        $filter = $this->ldapParams['mail'] . '=' . ldap_escape($email, '', LDAP_ESCAPE_FILTER);
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $this->getDefaultAttributes());
    }

    /**
     * Search if a LDAP user match a filter defined in local conf.
     *
     * @param string $words User name to search
     *
     * @return LDAPResultIterator|false
     */
    public function searchUser($words)
    {
        $words  = ldap_escape($words, '', LDAP_ESCAPE_FILTER);
        $filter = str_replace("%words%", $words, $this->ldapParams['search_user']);
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $this->getDefaultAttributes());
    }

    /**
     * Search if given identifier match a Common Name in the LDAP.
     *
     * @param string $name Common name to search
     *
     * @return LDAPResultIterator|false
     */
    public function searchCommonName($name)
    {
        $filter = $this->ldapParams['cn'] . '=' . ldap_escape($name, '', LDAP_ESCAPE_FILTER);
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE, $this->getDefaultAttributes());
    }

    /**
     * Search ldap group by name
     *
     * @param string $name Group name to search
     *
     * @return LDAPResultIterator|false
     */
    public function searchGroup($name)
    {
        $name = ldap_escape($name, '', LDAP_ESCAPE_FILTER);

        if (isset($this->ldapParams['server_type']) && $this->ldapParams['server_type'] === self::SERVER_TYPE_ACTIVE_DIRECTORY) {
            $filter = $this->ldapParams['grp_uid'] . '=' . $name;
            return $this->search($this->ldapParams['grp_dn'], $filter, self::SCOPE_SUBTREE);
        }

        $filter = $this->ldapParams['grp_cn'] . '=' . $name;
        return $this->search($this->ldapParams['dn'], $filter, self::SCOPE_SUBTREE);
    }

    /**
     * List members of a LDAP group
     *
     * @param string $groupDn Group DN
     *
     * @return LDAPResultIterator|false
     */
    public function searchGroupMembers($groupDn)
    {
        return $this->search($groupDn, 'objectClass=*', self::SCOPE_SUBTREE, [$this->ldapParams['grp_member']]);
    }

    /**
     * Specific search of user common name, only the common name is returned
     *
     * This method is designed for speed and to limit the number of returned values.
     *
     * @param string   $name      Name of the group to look for
     * @param int $sizeLimit Limit the amount of result sent
     *
     * @return AppendIterator
     */
    public function searchUserAsYouType($name, $sizeLimit, $validEmail = false)
    {
        $apIt = new AppendIterator();
        if ($name && $this->_connectAndBind()) {
            $name = ldap_escape($name, '', LDAP_ESCAPE_FILTER);
            if (isset($this->ldapParams['tooltip_search_user'])) {
                $filter = str_replace("%words%", $name, $this->ldapParams['tooltip_search_user']);
            } else {
                $filter = '(' . $this->ldapParams['cn'] . '=' . $name . '*)';
            }
            if ($validEmail) {
                // Only search people with a non empty mail field
                $mail   = ldap_escape($this->ldapParams['mail'], '', LDAP_ESCAPE_FILTER);
                $filter = '(&' . $filter . '(' . $mail . '=*))';
            }
            // We only care about Common name and Login (lower the amount of data
            // to fetch speed up the request.
            if (isset($this->ldapParams['tooltip_search_attrs'])) {
                $attrs = explode(';', $this->ldapParams['tooltip_search_attrs']);
            } else {
                $attrs = [$this->ldapParams['cn'], $this->ldapParams['uid']];
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
                $this->logger->debug('LDAP in-depth search as you type ' . $filter . ' *** PEOPLEDN: ' . implode(',', $peopleDn) . ' *** errors:' .  ldap_error($this->ds));
            } else {
                /** @psalm-suppress InvalidArgument $peopleDn can be an array, this will be fixed when we upgrade to PHP 8.0 https://github.com/php/php-src/blob/php-8.0.9/ext/ldap/ldap_arginfo.h#L55 */
                $asr = ldap_list($ds, $peopleDn, $filter, $attrs, $attrsOnly, $sizeLimit, 0, LDAP_DEREF_NEVER);
                $this->logger->debug('LDAP high-level search as you type ' . $filter . ' *** PEOPLEDN: ' . implode(',', $peopleDn) . ' *** errors:' .  ldap_error($this->ds));
            }
            if ($asr !== false) {
                assert(is_iterable($asr));
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
     * @param string   $name      Name of the group to look for
     * @param int $sizeLimit Limit the amount of result sent
     *
     * @return LDAPResultIterator
     */
    public function searchGroupAsYouType($name, $sizeLimit)
    {
        $lri = false;
        if ($this->_connectAndBind()) {
            $name = ldap_escape($name, '', LDAP_ESCAPE_FILTER);
            // Use display name if setting is found. Otherwise, fall back on old hard-coded filter.
            if (isset($this->ldapParams['tooltip_search_grp'])) {
                $filter = str_replace("%words%", $name, $this->ldapParams['tooltip_search_grp']);
            } else {
                $filter = '(' . $this->ldapParams['grp_cn'] . '=*' . $name . '*)';
            }
            if (isset($this->ldapParams['grp_display_name'])) {
                $attrs = [$this->ldapParams['grp_cn'], $this->ldapParams['grp_display_name']];
            } else {
                $attrs = [$this->ldapParams['grp_cn']];
            }
            // We want types and values
            $attrsOnly = 0;
            // Catch errors to detect if there are more results available than
            // the list actually returned (helps to refine the search)
            $this->trapErrors();
            $lri = $this->search($this->ldapParams['grp_dn'], $filter, $this->getSearchGroupScope(), $attrs, $attrsOnly, $sizeLimit);
        }
        if ($lri === false) {
            return new LDAPResultIterator([], []);
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

    /**
     * Enable fake error handler
     *
     * The fake error handler is enabled only for one query.
     *
     * @see _initErrorHandler()
     */
    private function trapErrors()
    {
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
    private function _initErrorHandler()
    {
        if ($this->errorsTrapped) {
            /**
             * @psalm-var callable(int, string, string=, int=, array<array-key, mixed>=):bool|null
             */
            $silent_error_handler = function ($errno, $errstr): void {
            };
            set_error_handler($silent_error_handler);
        }
    }

    /**
     * After LDAP query, restore the PHP error handler to its previous state.
     */
    private function _restoreErrorHandler()
    {
        if ($this->errorsTrapped) {
            restore_error_handler();
        }
        $this->errorsTrapped = false;
    }
}
