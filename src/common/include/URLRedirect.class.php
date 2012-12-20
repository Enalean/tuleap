<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * URLRedirect
 *
 * This class is responsible for
 * the redirection.
 */
class URLRedirect {

    /**
     * Build the redirection of user to the login page.
     */
    public function buildReturnToLogin($server){
        $returnTo = urlencode((($server['REQUEST_URI'] === "/") ? "/my/" : $server['REQUEST_URI']));
        $url = parse_url($server['REQUEST_URI']);
        if (isset($url['query'])) {
            $query = $url['query'];
            if (strstr($query, 'pv=2')) {
                $returnTo .= "&pv=2";
            }
        }
        if (strpos($url['path'], '/projects') === 0) {
            $GLOBALS['Response']->send401UnauthorizedHeader();
        }

        $url = '/account/login.php?return_to=' . $returnTo;
        return $url;
    }

    public function redirectToLogin(){
        $url = $this->buildReturnToLogin($_SERVER);
        $GLOBALS['HTML']->redirect($url);
    }
}

?>
