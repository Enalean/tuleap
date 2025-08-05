<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1\ArtifactSection\Field;

use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkStatusValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkValue;

/**
 * @psalm-immutable
 */
final readonly class ArtifactLinkValueRepresentation
{
    public string $link_label;
    public string $tracker_shortname;
    public string $tracker_color;
    public ArtifactLinkProjectReference $project;
    public int $artifact_id;
    public string $title;
    public string $html_uri;
    public ?ArtifactLinkStatusValueRepresentation $status;

    public function __construct(ArtifactLinkValue $link_value)
    {
        $this->link_label        = $link_value->link_label;
        $this->tracker_shortname = $link_value->tracker_shortname;
        $this->tracker_color     = $link_value->tracker_color->value;
        $this->project           = new ArtifactLinkProjectReference($link_value->project);
        $this->artifact_id       = $link_value->artifact_id;
        $this->title             = $link_value->title;
        $this->html_uri          = $link_value->html_uri;
        /** @psalm-suppress ImpureMethodCall */
        $this->status = $link_value->status->mapOr(
            static fn(ArtifactLinkStatusValue $status) => new ArtifactLinkStatusValueRepresentation($status),
            null
        );
    }
}
