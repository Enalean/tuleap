<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Tuleap\Event\Dispatchable;

final class CollectMovableExternalFieldEvent implements Dispatchable
{
    public const NAME = "collectMovableExternalFieldEvent";

    private const FIELD_FULLY_MIGRATEABLE     = "fully_migrateable";
    private const FIELD_PARTIALLY_MIGRATEABLE = "partially_migrateable";
    private const FIELD_NOT_MIGRATEABLE       = "not_migrateable";

    private string $migration_state = "";

    public function __construct(
        private readonly \Tracker_FormElement_Field $source_field,
        private readonly \Tracker_FormElement_Field $destination_field,
    ) {
    }

    public function getSourceField(): \Tracker_FormElement_Field
    {
        return $this->source_field;
    }

    public function getDestinationField(): \Tracker_FormElement_Field
    {
        return $this->destination_field;
    }

    public function markFieldAsFullyMigrateable(): void
    {
        $this->migration_state = self::FIELD_FULLY_MIGRATEABLE;
    }

    public function markFieldAsNotMigrateable(): void
    {
        $this->migration_state = self::FIELD_NOT_MIGRATEABLE;
    }

    public function isFieldMigrateable(): bool
    {
        return $this->migration_state === self::FIELD_FULLY_MIGRATEABLE || $this->migration_state === self::FIELD_PARTIALLY_MIGRATEABLE;
    }

    public function isFieldFullyMigrateable(): bool
    {
        return $this->migration_state === self::FIELD_FULLY_MIGRATEABLE;
    }
}
