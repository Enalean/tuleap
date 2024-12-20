<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Config;

/**
 * @psalm-immutable
 */
final class ConfigKeyMetadata
{
    public function __construct(
        public string $description,
        public ConfigKeyModifier $can_be_modified,
        public bool $is_secret,
        public bool $is_hidden,
        public readonly bool $has_default_value,
        public ?SecretValidator $secret_validator,
        public ?ValueValidator $value_validator,
        public ?string $category,
    ) {
    }
}
