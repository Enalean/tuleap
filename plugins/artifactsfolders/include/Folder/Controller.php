<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use PFUser;
use Tuleap\Tracker\Artifact\Artifact;

class Controller
{
    /**
     * @var ArtifactPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(ArtifactPresenterBuilder $presenter_builder)
    {
        $this->presenter_builder = $presenter_builder;
    }

    public function getChildren(PFUser $user, Artifact $artifact)
    {
        $artifact_representations = $this->presenter_builder->buildIsChild($user, $artifact);

        $GLOBALS['Response']->sendJSON($artifact_representations);
    }
}
