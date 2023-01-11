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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub;

use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\Link\FilterArtifactLink;
use Tuleap\Tracker\Artifact\Link\ForwardLinkProxy;

final class FilterArtifactLinkStub implements FilterArtifactLink
{
    private function __construct(private CollectionOfForwardLinks $forward_links)
    {
    }

    public function filterArtifactIdsIAmAlreadyLinkedTo(Artifact $artifact, Tracker_FormElement_Field_ArtifactLink $field, CollectionOfForwardLinks $collection_of_forward_links): CollectionOfForwardLinks
    {
        return $this->forward_links;
    }

    public static function withArtifactIdsIAmAlreadyLinkedTo(?string $linked_artifact_id): self
    {
        return new self(new CollectionOfForwardLinks([ForwardLinkProxy::buildFromData((int) $linked_artifact_id, "")]));
    }
}
