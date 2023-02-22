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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub;

use PFUser;
use Tuleap\Tracker\Artifact\Artifact;

final class ArtifactUserCanViewStub extends Artifact
{
    public $id;

    private function __construct(int $id, private bool $user_can_view)
    {
        $this->id = $id;
    }

    public static function buildUserCanViewArtifact(int $artifact_id): self
    {
        return new self($artifact_id, true);
    }

    public static function buildUserCannotViewArtifact(int $artifact_id): self
    {
        return new self($artifact_id, false);
    }

    public function userCanView(?PFUser $user = null): bool
    {
        return $this->user_can_view;
    }
}
