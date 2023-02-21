<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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

namespace Tuleap\REST;

use Luracast\Restler\RestException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

/**
 * @psalm-type FlatRepresentation = array<string,scalar|null|list<scalar|null>>
 */
final class RESTCollectionTransformer
{
    /**
     * @template R
     *
     * @psalm-param list<R> $representations
     * @psalm-param callable(R): (Ok<FlatRepresentation>|Err<Fault>) $flattener
     * @psalm-return list<FlatRepresentation>
     * @throws RestException
     */
    public static function flattenRepresentations(array $representations, callable $flattener): array
    {
        $flat_representations = [];
        foreach ($representations as $representation) {
            $flat_representations[] = ($flattener($representation))->match(
                fn (array $flat_representation): array => $flat_representation,
                function (Fault $fault): never {
                    throw new RestException(400, (string) $fault);
                },
            );
        }

        return $flat_representations;
    }
}
