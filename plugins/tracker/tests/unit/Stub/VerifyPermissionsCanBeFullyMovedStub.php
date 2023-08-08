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

use Psr\Log\LoggerInterface;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\Tracker\Action\VerifyPermissionsCanBeFullyMoved;
use Tuleap\Tracker\Artifact\Artifact;

final class VerifyPermissionsCanBeFullyMovedStub implements VerifyPermissionsCanBeFullyMoved
{
    public function __construct(private readonly bool $will_be_fully_moved)
    {
    }

    public static function withPartialMove(): self
    {
        return new self(false);
    }

    public static function withCompleteMove(): self
    {
        return new self(true);
    }

    public function canAllPermissionsBeFullyMoved(Tracker_FormElement_Field_PermissionsOnArtifact $source_field, Tracker_FormElement_Field_PermissionsOnArtifact $destination_field, Artifact $artifact, LoggerInterface $logger): bool
    {
        return $this->will_be_fully_moved;
    }
}
