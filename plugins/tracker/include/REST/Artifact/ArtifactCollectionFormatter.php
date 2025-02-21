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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\REST\RESTCollectionTransformer;

/**
 * @psalm-import-type FlatRepresentation from RESTCollectionTransformer
 */
final readonly class ArtifactCollectionFormatter
{
    public function __construct(
        private FlatArtifactRepresentationTransformer $flat_transformer,
        private FlatArtifactRepresentationTransformer $flat_with_semicolon_string_array_transformer,
    ) {
    }

    /**
     * @template T
     * @psalm-param list<ArtifactRepresentation> $representations
     * @psalm-param callable(list<ArtifactRepresentation>):T $nested_transformation
     * @psalm-return list<FlatRepresentation>|T
     */
    public function format(
        ArtifactCollectionFormat $format,
        array $representations,
        callable $nested_transformation,
    ): array {
        return match ($format) {
            ArtifactCollectionFormat::FLAT => RESTCollectionTransformer::flattenRepresentations(
                $representations,
                $this->flat_transformer
            ),
            ArtifactCollectionFormat::FLAT_WITH_SEMICOLON_STRING_ARRAY => RESTCollectionTransformer::flattenRepresentations(
                $representations,
                $this->flat_with_semicolon_string_array_transformer
            ),
            ArtifactCollectionFormat::NESTED => $nested_transformation($representations),
        };
    }
}
