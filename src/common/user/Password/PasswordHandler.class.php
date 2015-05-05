<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

abstract class PasswordHandler {
    public abstract function verifyHashPassword($plain_password, $hash_password);

    public abstract function computeHashPassword($plain_password);

    public abstract function isPasswordNeedRehash($hash_password);

    /**
     * Generate a random number between 46 and 122
     *
     * @return Integer
     */
    private function ranNum() {
        mt_srand();
        $num = mt_rand(46,122);
        return $num;
    }

    /**
     * Generate a random alphanum character
     *
     * @return String
     */
    private function genChr() {
        do {
            $num = $this->ranNum();
        } while ( ( $num > 57 && $num < 65 ) || ( $num > 90 && $num < 97 ) );
        $char = chr($num);
        return $char;
    }

    private function genStr($length) {
        $res = '';
        for ($i = $length; $i > 0; $i--) {
            $res .= $this->genChr();
        }
        return $res;
    }

    /**
     * Generate Unix shadow password
     *
     * @param String $plain_password Clear password
     *
     * @return String
     */
    public function computeUnixPassword($plain_password) {
        // (LJ) Adding $1$ at the beginning of the salt
        // forces the MD5 encryption so the system has to
        // have MD5 pam module installed for Unix passwd file.
        return crypt($plain_password, '$1$' . $this->genStr(2));
    }
}