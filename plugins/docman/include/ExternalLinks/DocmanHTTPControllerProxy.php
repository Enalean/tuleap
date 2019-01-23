<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\ExternalLinks;

class DocmanHTTPControllerProxy
{
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var ExternalLinkParametersExtractor
     */
    private $parameters_extractor;
    /**
     * @var \Docman_HTTPController
     */
    private $docman_HTTP_controller;

    public function __construct(
        \EventManager $event_manager,
        ExternalLinkParametersExtractor $parameters_extractor,
        \Docman_HTTPController $docman_HTTP_controller
    ) {
        $this->event_manager          = $event_manager;
        $this->parameters_extractor   = $parameters_extractor;
        $this->docman_HTTP_controller = $docman_HTTP_controller;
    }

    public function process(\HTTPRequest $request, \PFUser $user) : void
    {
        $folder_id = $this->parameters_extractor->extractFolderIdFromParams($request);

        $redirector = new ExternalLinkRedirector(
            $user,
            $request->getProject(),
            $folder_id
        );

        $this->processEventWhenNeeded($request, $redirector);

        if ($redirector->shouldRedirectUserOnNewUI()) {
            $GLOBALS['HTML']->redirect($redirector->getUrlRedirection());
        } else {
            $this->docman_HTTP_controller->process();
        }
    }


    private function processEventWhenNeeded(\HTTPRequest $request, ExternalLinkRedirector $redirector) : void
    {
        if ($this->parameters_extractor->extractRequestIsForOldUIParams($request)) {
            $this->event_manager->processEvent($redirector);
        }
    }
}
