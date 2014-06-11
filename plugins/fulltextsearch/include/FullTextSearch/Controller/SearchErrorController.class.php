<?php

/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
class FullTextSearch_Controller_SearchError extends MVC2_PluginController {

    public function __construct(Codendi_Request $request) {
        parent::__construct('fulltextsearch', $request);
    }

    public function clientNotFound($params) {
        $results_presenter = new FullTextSearch_Presenter_ErrorNoSearch($GLOBALS['Language']->getText('plugin_fulltextsearch', 'client_not_found'));

        $params['results'] = $this->renderToString($results_presenter->template, $results_presenter);
    }
}

?>
