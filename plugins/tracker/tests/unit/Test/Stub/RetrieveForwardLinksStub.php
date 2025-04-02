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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveForwardLinks;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;

final class RetrieveForwardLinksStub implements RetrieveForwardLinks
{
    private function __construct(private CollectionOfForwardLinks $links)
    {
    }

    public static function withLinks(CollectionOfForwardLinks $links): self
    {
        return new self($links);
    }

    public static function withoutLinks(): self
    {
        return new self(new CollectionOfForwardLinks([]));
    }

    public function retrieve(
        \PFUser $submitter,
        ArtifactLinkField $link_field,
        Artifact $artifact,
    ): CollectionOfForwardLinks {
        return $this->links;
    }
}
