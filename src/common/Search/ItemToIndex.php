<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Search;

use Tuleap\Event\Dispatchable;

/**
 * @psalm-immutable
 */
final class ItemToIndex implements Dispatchable
{
    public const NAME = 'indexItem';

    public const ALL_CONTENT_TYPES       = [self::CONTENT_TYPE_PLAINTEXT, self::CONTENT_TYPE_COMMONMARK, self::CONTENT_TYPE_HTML];
    public const CONTENT_TYPE_PLAINTEXT  = 'plaintext';
    public const CONTENT_TYPE_COMMONMARK = 'commonmark';
    public const CONTENT_TYPE_HTML       = 'html';

    /**
     * @psalm-param self::CONTENT_TYPE_* $content_type
     * @param non-empty-array<non-empty-string,string> $metadata
     */
    public function __construct(
        public string $type,
        public ?int $project_id,
        public string $content,
        public string $content_type,
        public array $metadata,
    ) {
    }
}
