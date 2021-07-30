<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class VerifyIsVisibleArtifactStub implements VerifyIsVisibleArtifact
{
    /**
     * @var int[]
     */
    private array $visible_artifact_ids;

    private function __construct(int ...$visible_ids)
    {
        $this->visible_artifact_ids = $visible_ids;
    }

    public function isVisible(int $artifact_id, UserIdentifier $user_identifier): bool
    {
        return in_array($artifact_id, $this->visible_artifact_ids, true);
    }

    public static function withVisibleIds(int ...$visible_artifact_ids): self
    {
        return new self(...$visible_artifact_ids);
    }
}
