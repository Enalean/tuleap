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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

/**
 * @psalm-immutable
 */
final class ArtifactLinksDiff
{
    /**
     * @param int[] $new_values
     * @param int[] $removed_values
     */
    private function __construct(
        private array $new_values,
        private array $removed_values,
    ) {
    }

    public static function build(CollectionOfArtifactLinks $submitted_links, CollectionOfArtifactLinksInfo $currently_linked_artifacts): self
    {
        $existing_links_ids  = $currently_linked_artifacts->getLinksInfoArtifactsIds();
        $submitted_links_ids = $submitted_links->getArtifactLinksIds();

        $new_values     = array_diff($submitted_links_ids, $existing_links_ids);
        $removed_values = array_diff($existing_links_ids, $submitted_links_ids);

        return new self(
            array_values($new_values),
            array_values($removed_values)
        );
    }

    /**
     * @return int[]
     */
    public function getNewValues(): array
    {
        return $this->new_values;
    }

    /**
     * @return int[]
     */
    public function getRemovedValues(): array
    {
        return $this->removed_values;
    }
}
