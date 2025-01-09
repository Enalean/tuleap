<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Artidoc\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Artidoc\Domain\Document\Section\SectionContentToBeCreated;

/**
 * @psalm-immutable
 */
final class ContentToBeCreatedBuilder
{
    public static function buildFromRepresentation(ArtidocSectionPOSTRepresentation $section): SectionContentToBeCreated
    {
        if ($section->artifact !== null && $section->content !== null) {
            throw new RestException(400, dgettext('tuleap-artidoc', "The properties 'artifact' and 'content' can not be used at the same time"));
        }

        $content = null;
        if ($section->artifact !== null) {
            $content = SectionContentToBeCreated::fromArtifact(
                $section->artifact->id
            );
        } elseif ($section->content) {
            $content = SectionContentToBeCreated::fromFreetext(
                $section->content->title,
                $section->content->description
            );
        }

        if (! $content) {
            throw new RestException(400, dgettext('tuleap-artidoc', 'No artifact id or section content provided'));
        }

        return $content;
    }
}
