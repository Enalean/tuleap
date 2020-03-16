<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Layout;

use HTTPRequest;
use Tuleap\Theme\BurningParrot\BurningParrotTheme;

class ErrorRendering
{
    private $presenter = [];

    public function rendersError(BurningParrotTheme $layout, HTTPRequest $request, $http_code, $title, $message)
    {
        $this->presenter['title']   = $title;
        $this->presenter['message'] = $message;

        http_response_code((int) $http_code);

        if ($request->isAjax()) {
            return;
        }

        $layout->header(
            [
                'title'        => $this->presenter['title'],
                'main_classes' => ['tlp-framed'],
            ]
        );

        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates/common');
        $renderer->renderToPage('http_error', $this->presenter);
        $layout->footer([]);
    }

    public function rendersErrorWithException(BurningParrotTheme $layout, HTTPRequest $request, $http_code, $title, $message, \Exception $exception)
    {
        if (isset($exception->xdebug_message) && ini_get('display_errors') === '1') {
            $this->presenter['xdebug_message'] = $exception->xdebug_message;
        }
        $this->rendersError($layout, $request, $http_code, $title, $message);
    }
}
