<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Artidoc\REST;

use Luracast\Restler\Restler;
use Tuleap\Artidoc\REST\v1\ArtidocFilesResource;
use Tuleap\Artidoc\REST\v1\ArtidocResource;
use Tuleap\Artidoc\REST\v1\ArtidocSectionsResource;

final readonly class ResourcesInjector
{
    public function populate(Restler $restler): void
    {
        $restler->addAPIClass(ArtidocResource::class, ArtidocResource::ROUTE);
        $restler->addAPIClass(ArtidocSectionsResource::class, ArtidocSectionsResource::ROUTE);
        $restler->addAPIClass(ArtidocFilesResource::class, ArtidocFilesResource::ROUTE);
    }
}
