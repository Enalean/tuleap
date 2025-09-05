<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchMeilisearch\Server;

use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Config\ValueValidator;

final class MeilisearchServerURLValidator implements ValueValidator
{
    private function __construct(private \Valid_HTTPSURI $valid_https_uri)
    {
    }

    #[\Override]
    public static function buildSelf(): self
    {
        return new self(new \Valid_HTTPSURI());
    }

    /**
     * @throws InvalidConfigKeyValueException
     */
    #[\Override]
    public function checkIsValid(string $value): void
    {
        if ($value === '') {
            throw new InvalidConfigKeyValueException('Meilisearch server URL cannot be empty');
        }

        if (! $this->valid_https_uri->validate($value)) {
            throw new InvalidConfigKeyValueException('Meilisearch server URL is not a valid HTTPS URL');
        }
    }
}
