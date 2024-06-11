<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

final readonly class FunctionLogLineToSave
{
    private function __construct(
        public FunctionLogLineStatus $status,
        public int $changeset_id,
        public string $source_payload_json,
        public ?string $generated_payload_json,
        public ?string $error_message,
        public int $execution_date,
    ) {
    }

    public static function buildPassed(
        int $changeset_id,
        string $source_payload_json,
        string $generated_payload_json,
        int $execution_date,
    ): self {
        return new self(
            FunctionLogLineStatus::PASSED,
            $changeset_id,
            $source_payload_json,
            $generated_payload_json,
            null,
            $execution_date,
        );
    }

    public static function buildError(
        int $changeset_id,
        string $source_payload_json,
        string $error_message,
        int $execution_date,
    ): self {
        return new self(
            FunctionLogLineStatus::ERROR,
            $changeset_id,
            $source_payload_json,
            null,
            $error_message,
            $execution_date,
        );
    }
}
