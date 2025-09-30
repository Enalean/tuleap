<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;

final class AllLinkPayloadParser
{
    /**
     * @param LinkWithDirectionRepresentation[] $all_links
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public static function buildReverseLinks(array $all_links): CollectionOfReverseLinks
    {
        $reverse_links = [];
        foreach ($all_links as $link) {
            if ($link->direction === LinkDirection::REVERSE->value) {
                $reverse_links[] = RESTReverseLinkProxy::fromPayload($link);
            }
        }

        return new CollectionOfReverseLinks($reverse_links);
    }

    /**
     * @param LinkWithDirectionRepresentation[] $all_links
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public static function buildForwardLinks(array $all_links): CollectionOfForwardLinks
    {
        $forward_links = [];
        foreach ($all_links as $link) {
            if ($link->direction === LinkDirection::FORWARD->value) {
                $forward_links[] = RESTForwardLinkProxy::fromAllLinksPayload($link);
            }
        }
        return new CollectionOfForwardLinks($forward_links);
    }
}
