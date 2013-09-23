<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class OpenId_OpenIdRouter {
    /** @var Logger */
    private $logger;

    const LOGIN                = 'login';
    const FINISH_LOGIN         = 'finish_login';
    const PAIR_ACCOUNTS        = 'pair_accounts';
    const FINISH_PAIR_ACCOUNTS = 'finish_pair_accounts';
    const REMOVE_PAIR          = 'remove_pair';

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function route(HTTPRequest $request, Layout $response) {
        $reflection   = new ReflectionClass(__CLASS__);
        $valid_route  = new Valid_WhiteList('func', $reflection->getConstants());
        if ($request->valid($valid_route)) {
            $route = $request->get('func');
            $controller = new OpenId_LoginController($this->logger, $request, $response);
            $controller->$route();
        } else {
            $response->addFeedback(Feedback::ERROR, 'Invalid request for '.__CLASS__.': ('.implode(', ', $reflection->getConstants()).')');
            $response->redirect('/');
        }
    }
}

?>
