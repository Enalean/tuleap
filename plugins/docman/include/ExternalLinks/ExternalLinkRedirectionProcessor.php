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

class ExternalLinkRedirectionProcessor
{
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var ExternalLinkParametersExtractor
     */
    private $parameters_extractor;

    public function __construct(\EventManager $event_manager, ExternalLinkParametersExtractor $parameters_extractor)
    {
        $this->event_manager        = $event_manager;
        $this->parameters_extractor = $parameters_extractor;
    }

    public function processIfPossible(\HTTPRequest $request, \PFUser $user) : bool
    {
        $folder_id = $this->parameters_extractor->extractFolderIdFromParams($request);

        $redirector = new ExternalLinkRedirector(
            $user,
            $request->getProject(),
            $folder_id
        );

        $this->processEventWhenNeeded($request, $redirector);

        if ($redirector->shouldRedirectUser()) {
            $GLOBALS['HTML']->redirect($redirector->getUrlRedirection());
            return true;
        }

        return false;
    }


    private function processEventWhenNeeded(\HTTPRequest $request, ExternalLinkRedirector $redirector) : void
    {
        if ($this->parameters_extractor->extractRequestIsForOldUIParams($request)) {
            $this->event_manager->processEvent($redirector);
        }
    }
}
