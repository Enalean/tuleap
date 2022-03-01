<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue\CollectionOfArtifactLinksInfo;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue\RetrieveForwardLinksInfo;

final class RetrieveForwardLinksInfoStub implements RetrieveForwardLinksInfo
{
    private function __construct(private CollectionOfArtifactLinksInfo $links_info)
    {
    }

    public static function withLinksInfo(): self
    {
        return new self(
            new CollectionOfArtifactLinksInfo([
                new \Tracker_ArtifactLinkInfo(101, 'story', 101, 123, 1, '_is_child'),
                new \Tracker_ArtifactLinkInfo(102, 'story', 101, 123, 2, '_is_child'),
                new \Tracker_ArtifactLinkInfo(103, 'story', 101, 123, 3, '_is_child'),
            ])
        );
    }

    public function retrieve(
        \PFUser $submitter,
        Tracker_FormElement_Field_ArtifactLink $link_field,
        ?Artifact $artifact,
    ): CollectionOfArtifactLinksInfo {
        return $this->links_info;
    }
}
