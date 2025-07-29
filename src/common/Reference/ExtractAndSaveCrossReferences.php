<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Reference;

interface ExtractAndSaveCrossReferences
{
    /**
     * Extract References from a given text and insert extracted refs into the database
     *
     * @param string      $html        Text to parse
     * @param int|string  $source_id   Id of the item where the text was added
     * @param string      $source_type Nature of the source
     * @param int|string  $source_gid  Project Id of the project the source item belongs to
     * @param int|string  $user_id     User who owns the text to parse
     * @param string|null $source_key  Keyword to use for the reference (if different from the one associated to the nature)
     */
    public function extractCrossRef(
        mixed $html,
        int|string $source_id,
        string $source_type,
        int|string $source_gid,
        int|string $user_id = \PFUser::ANONYMOUS_USER_ID,
        ?string $source_key = null,
    ): true;
}
