<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * This class is a wrapper for call to api/reference/extractCross
 *
 * This allow applications that run on the server to extract cross references
 * without having access to the database
 */
class Git_Hook_ExtractCrossReferences {

    public function extract($project_name, $user_name, $type, $rev_id, $text) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/reference/extractCross');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Codendi Perl Agent');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'group_name' => $project_name,
            'login'      => $user_name,
            'type'       => $type,
            'rev_id'     => $rev_id,
            'text'       => $text
        )));

        $result = curl_exec($ch);

        curl_close($ch);
        return $result;
    }
}

?>
