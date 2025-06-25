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

namespace Tuleap\Tracker\Semantic;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

/**
 * @psalm-immutable
 */
final class CollectionOfCreationSemanticToCheck
{
    /**
     * @param CreationSemanticToCheck[] $semantics
     */
    private function __construct(public readonly array $semantics)
    {
    }

    /**
     * @param string[] $semantics
     * @return Ok<self>|Err<Fault>
     */
    public static function fromREST(array $semantics): Ok|Err
    {
        $semantics_to_check_collection = [];
        foreach ($semantics as $semantic) {
            $result = CreationSemanticToCheck::fromREST($semantic);
            if (Result::isErr($result)) {
                return Result::err($result->error);
            }
            $semantics_to_check_collection[] = $result->value;
        }
        return Result::ok(new self($semantics_to_check_collection));
    }

    public function isEmpty(): bool
    {
        return empty($this->semantics);
    }
}
