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

class Admin_PermissionDelegationController {

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    public function __construct(Codendi_Request $request) {
        $this->request  = $request;
        $this->renderer = TemplateRendererFactory::build()->getRenderer($this->getTemplatesDir());
    }

    public function process() {
        switch ($this->request->get('action')) {
            case 'index':
            default     :
                $this->index();
                break;
        }
    }

    private function index() {
        $presenter = new Admin_PermissionDelegationIndexPresenter();
        $this->renderer->renderToPage('index', $presenter);
    }

    private function getTemplatesDir() {
        return Config::get('codendi_dir') .'/src/templates/admin/permission_delegation/';
    }
}
?>
