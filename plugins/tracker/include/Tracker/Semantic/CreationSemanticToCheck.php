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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;

/**
 * @psalm-immutable
 */
final class CreationSemanticToCheck
{
    private const SUPPORTED_SEMANTICS = [TrackerSemanticTitle::NAME];

    private function __construct(public readonly string $semantic_to_check)
    {
    }

    /**
     * @return Ok<self>|Err<Fault>
     */
    public static function fromREST(string $semantic_to_check): Ok|Err
    {
        if (! in_array($semantic_to_check, self::SUPPORTED_SEMANTICS)) {
            return Result::err(SemanticNotSupportedFault::fromSemanticName($semantic_to_check));
        }
        return Result::ok(new self($semantic_to_check));
    }

    public function isSemanticTitle(): bool
    {
        return $this->semantic_to_check === TrackerSemanticTitle::NAME;
    }
}
