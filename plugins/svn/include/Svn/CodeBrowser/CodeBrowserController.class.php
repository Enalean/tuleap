<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

namespace Tuleap\Svn\CodeBrowser;

use Tuleap\Svn\ServiceSvn;
use Tuleap\Svn\ViewVCProxy\ViewVCProxy;

use HTTPRequest;

class CodeBrowserController {
    const NAME = 'code-browser';
    const DEBUG_FLAG = FALSE;
    private $proxy;

    public function __construct() {
        $this->proxy = new ViewVCProxy();
    }

    public function getName() {
        return self::NAME;
    }

    public function index(ServiceSvn $service, HTTPRequest $request) {
        // TODO: make sure all ViewVC functionnalities are still operationnal
        $project = $request->getProject();

        $service->renderInPage(
            $request,
            'ViewVC',
            'code-browser/index',
            new CodeBrowserPresenter(
                $project,
                $this->proxy->getContent($request)
            )
        );
    }
}
