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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\ArtifactLinks\DeletedArtifactLinksEvent;

final class DeletedArtifactLinksEventStub implements DeletedArtifactLinksEvent
{
    private function __construct(
        private int $call_count,
        private int $updated_artifact_id,
        private array $deleted_links,
    ) {
    }

    public static function withData(int $updated_artifact_id, array $deleted_links): self
    {
        return new self(
            0,
            $updated_artifact_id,
            $deleted_links,
        );
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }

    #[\Override]
    public function getUpdatedArtifactId(): int
    {
        return $this->updated_artifact_id;
    }

    #[\Override]
    public function getDeletedArtifactLinksIds(): array
    {
        return $this->deleted_links;
    }

    #[\Override]
    public function setDeletedLinksAreNotValidWithMessage(string $message): void
    {
        $this->call_count++;
    }
}
