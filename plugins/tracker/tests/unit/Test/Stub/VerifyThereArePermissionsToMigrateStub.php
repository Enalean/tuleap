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

namespace Tuleap\Tracker\Test\Stub;

use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\Tracker\Action\VerifyThereArePermissionsToMigrate;
use Tuleap\Tracker\Artifact\Artifact;

final class VerifyThereArePermissionsToMigrateStub implements VerifyThereArePermissionsToMigrate
{
    public function __construct(private readonly bool $with_permissions_to_migrate)
    {
    }

    public static function withPermissionsToMigrate(): self
    {
        return new self(true);
    }

    public static function withoutPermissionsToMigrate(): self
    {
        return new self(false);
    }

    public function areTherePermissionsToMigrate(Tracker_FormElement_Field_PermissionsOnArtifact $source_field, Artifact $artifact): bool
    {
        return $this->with_permissions_to_migrate;
    }
}
