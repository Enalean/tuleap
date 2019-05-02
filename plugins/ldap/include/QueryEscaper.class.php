<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * Provide utility functions to query an LDAP directory
 *
 */
class LdapQueryEscaper {
    const LDAP_ESCAPE_FILTER = 1;
    const LDAP_ESCAPE_DN     = 2;

    /**
     * Escape strings for safe use in an LDAP filter or DN
     *
     * @see RFC2254 define how string search filters must be represented
     * @see For PHP >= 5.6.0, ldap_escape() is a core function
     *
     * @see https://github.com/DaveRandom/LDAPi/blob/master/src/global_functions.php
     *
     * @return String
     */
    private function escape($value, $ignore = '', $flags = 0) {
        if(function_exists('ldap_escape')) {
            return ldap_escape($value, $ignore, $flags);
        }

        $value = (string) $value;
        $ignore = (string) $ignore;
        $flags = (int) $flags;

        if ($value === '') {
            return '';
        }

        $char_list = array();
        if ($flags & self::LDAP_ESCAPE_FILTER) {
            $char_list = array("\\", "*", "(", ")", "\x00");
        }
        if ($flags & self::LDAP_ESCAPE_DN) {
            $char_list = array_merge($char_list, array("\\", ",", "=", "+", "<", ">", ";", "\"", "#"));
        }
        if (!$char_list) {
            for ($i = 0; $i < 256; $i++) {
                $char_list[] = chr($i);
            }
        }
        $char_list = array_flip($char_list);

        for ($i = 0; isset($ignore[$i]); $i++) {
            unset($char_list[$ignore[$i]]);
        }

        foreach ($char_list as $k => &$v) {
            $v = sprintf('\%02x', ord($k));
        }

        return strtr($value, $char_list);
    }

    public function escapeFilter($value) {
        return $this->escape($value, '', self::LDAP_ESCAPE_FILTER);
    }
}
