<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\ArtifactsFolders\Folder;

use HTTPRequest;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use Tracker_ArtifactFactory;
use Tracker_URLVerification;

class Router
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_URLVerification
     */
    private $url_verification;

    /**
     * @var Controller
     */
    private $controller;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_URLVerification $url_verification,
        Controller $controller,
    ) {
        $this->artifact_factory = $artifact_factory;
        $this->url_verification = $url_verification;
        $this->controller       = $controller;
    }

    public function route(HTTPRequest $request)
    {
        if (! $request->isAjax()) {
            return;
        }

        if ($request->get('action') !== 'get-children') {
            return;
        }

        $this->controller->getChildren($request->getCurrentUser(), $this->getArtifactFromRequest($request));
    }

    private function getArtifactFromRequest(HTTPRequest $request)
    {
        $artifact = $this->artifact_factory->getArtifactById($request->get('aid'));
        if (! $artifact) {
            $this->error(404, "Not Found");
        }

        $user = $request->getCurrentUser();
        if (! $artifact->userCanView($user)) {
            $this->error(403, "Forbidden");
        }

        try {
            $this->url_verification->userCanAccessProject($user, $artifact->getTracker()->getProject());
        } catch (Project_AccessProjectNotFoundException $exception) {
            $this->error(404, "Not Found");
        } catch (Project_AccessException $exception) {
            $this->error(403, "Forbidden");
        }

        return $artifact;
    }

    private function error($code, $reason)
    {
        header("HTTP/1.1 $code $reason");
        exit;
    }
}
