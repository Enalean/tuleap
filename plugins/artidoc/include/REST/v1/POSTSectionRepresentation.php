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

namespace Tuleap\Artidoc\REST\v1;

/**
 * @psalm-immutable
 */
final class POSTSectionRepresentation
{
    /**
     * @var \Tuleap\Artidoc\REST\v1\POSTSectionImportRepresentation | null The already existing content to import {@type \Tuleap\Artidoc\REST\v1\POSTSectionImportRepresentation} {@required false}
     */
    public ?POSTSectionImportRepresentation $import = null;

    /**
     * @var \Tuleap\Artidoc\REST\v1\POSTContentSectionRepresentation | null The content to create {@type \Tuleap\Artidoc\REST\v1\POSTContentSectionRepresentation} {@required false}
     */
    public ?POSTContentSectionRepresentation $content = null;

    /**
     * @var \Tuleap\Artidoc\REST\v1\POSTSectionPositionBeforeRepresentation | null The position {@type \Tuleap\Artidoc\REST\v1\POSTSectionPositionBeforeRepresentation} {@required false}
     */
    public mixed $position;

    public function __construct(
        ?POSTSectionImportRepresentation $import,
        ?POSTSectionPositionBeforeRepresentation $position,
        ?POSTContentSectionRepresentation $content,
    ) {
        $this->import   = $import;
        $this->position = $position;
        $this->content  = $content;
    }
}
