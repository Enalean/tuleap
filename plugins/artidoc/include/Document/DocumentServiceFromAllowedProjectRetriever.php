<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Artidoc\Document;

use DocmanPlugin;
use Project;
use ServiceTracker;
use trackerPlugin;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Plugin\IsProjectAllowedToUsePlugin;

final readonly class DocumentServiceFromAllowedProjectRetriever
{
    public function __construct(private IsProjectAllowedToUsePlugin $plugin)
    {
    }

    /**
     * @return Ok<ServiceDocman>|Err<Fault>
     */
    public function getDocumentServiceFromAllowedProject(Project $project): Ok|Err
    {
        if (! $this->plugin->isAllowed((int) $project->getID())) {
            return Result::err(Fault::fromMessage('Project is not allowed to use artidoc'));
        }


        if (! $project->getService(trackerPlugin::SERVICE_SHORTNAME) instanceof ServiceTracker) {
            return Result::err(Fault::fromMessage('Project does not have tracker service enabled'));
        }

        $service = $project->getService(DocmanPlugin::SERVICE_SHORTNAME);
        if (! $service instanceof ServiceDocman) {
            return Result::err(Fault::fromMessage('Project does not have docman service enabled'));
        }

        return Result::ok($service);
    }
}
