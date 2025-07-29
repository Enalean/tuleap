<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Stubs\Document\Field\Date;

use Override;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Date;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Document\Field\Date\BuildDateFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\DateFieldWithValue;

final class BuildDateFieldWithValueStub implements BuildDateFieldWithValue
{
    /** @psalm-var callable(ConfiguredField): DateFieldWithValue */
    private $callback;

    private function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public static function withCallback(callable $callback): self
    {
        return new self($callback);
    }

    #[Override]
    public function buildDateFieldWithValue(
        ConfiguredField $configured_field,
        Tracker_Artifact_Changeset $changeset,
        ?Tracker_Artifact_ChangesetValue_Date $changeset_value,
    ): DateFieldWithValue {
        return ($this->callback)($configured_field);
    }
}
