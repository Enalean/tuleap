<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Logs;

use Tuleap\Date\TlpRelativeDatePresenter;

final class LogLinePresenter
{
    public function __construct(
        public readonly int $id,
        public readonly bool $is_error,
        public readonly TlpRelativeDatePresenter $execution_date_block,
        public readonly TlpRelativeDatePresenter $execution_date_inline,
        public readonly string $type,
        public readonly string $url,
        public readonly string $xref,
        public readonly string $title,
        public readonly string $color,
        public readonly string $source_payload_json,
        public readonly ?string $generated_payload_json,
        public readonly ?string $error_message,
    ) {
    }
}
