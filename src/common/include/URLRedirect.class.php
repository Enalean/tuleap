<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
class URLRedirect
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    /**
     * Build the redirection of user to the login page.
     *
     * @psalm-param array{REQUEST_URI:string} $server
     */
    public function buildReturnToLogin($server)
    {
        $returnTo = $server['REQUEST_URI'];
        if (
            $server['REQUEST_URI'] === '/' ||
            strpos($server['REQUEST_URI'], '/account/login.php') === 0 ||
            strpos($server['REQUEST_URI'], '/account/register.php') === 0
        ) {
            $returnTo = '/my/';
        }
        $url        = parse_url($server['REQUEST_URI']);
        $print_view = '';
        if (isset($url['query'])) {
            $query = $url['query'];
            if (strstr($query, 'pv=2')) {
                $print_view = '&pv=2';
            }
        }

        $url = '/account/login.php?return_to=' . urlencode($returnTo) . $print_view;
        $this->event_manager->processEvent(Event::GET_LOGIN_URL, array(
            'return_to' => $returnTo,
            'login_url' => &$url
        ));
        return $url;
    }

    public function redirectToLogin()
    {
        $url = $this->buildReturnToLogin($_SERVER);
        $GLOBALS['HTML']->redirect($url);
    }

    public function makeReturnToUrl($url, $return_to)
    {
        $urlToken = parse_url($url);

        $server_url = '';
        if (($urlToken['host'] ?? '') !== '') {
            $server_url = ($urlToken['scheme'] ?? '') . '://' . ($urlToken['host'] ?? '');
            if (($urlToken['port'] ?? '') !== '') {
                $server_url .= ':' . ($urlToken['port'] ?? '');
            }
        }

        $finaleUrl = $server_url;

        if (($urlToken['path'] ?? '') !== '') {
            $finaleUrl .= ($urlToken['path'] ?? '');
        }

        if ($return_to) {
            $return_to_parameter = 'return_to=';
            /*
             * We do not want redirect to an external website
             * @see https://cwe.mitre.org/data/definitions/601.html
             */
            $url_verifier = new URLVerification();
            if ($url_verifier->isInternal($return_to)) {
                $return_to_parameter .= urlencode($return_to);
            } else {
                $return_to_parameter .= '/';
            }

            if (($urlToken['query'] ?? '') !== '') {
                $finaleUrl .= '?' . ($urlToken['query'] ?? '') . '&amp;' . $return_to_parameter;
            } else {
                $finaleUrl .= '?' . $return_to_parameter;
            }
            if (strstr($return_to, 'pv=2')) {
                $finaleUrl .= '&pv=2';
            }
        } else {
            if (($urlToken['query'] ?? '') !== '') {
                $finaleUrl .= '?' . ($urlToken['query'] ?? '');
            }
        }

        if (($urlToken['fragment'] ?? '') !== '') {
            $finaleUrl .= '#' . ($urlToken['fragment'] ?? '');
        }

        return $finaleUrl;
    }
}
