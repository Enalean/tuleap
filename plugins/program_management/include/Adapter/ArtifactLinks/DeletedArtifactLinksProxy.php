<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\ArtifactLinks;

use Tuleap\ProgramManagement\Domain\ArtifactLinks\DeletedArtifactLinksEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ValidateArtifactLinkValueEvent;

class DeletedArtifactLinksProxy implements DeletedArtifactLinksEvent
{
    private function __construct(
        private ValidateArtifactLinkValueEvent $inner_event,
    ) {
    }

    public static function fromEvent(ValidateArtifactLinkValueEvent $validate_artifact_link_value_event): self
    {
        return new self($validate_artifact_link_value_event);
    }

    #[\Override]
    public function getUpdatedArtifactId(): int
    {
        return $this->inner_event->getArtifact()->getId();
    }

    /**
     * @return int[]
     */
    #[\Override]
    public function getDeletedArtifactLinksIds(): array
    {
        return $this->inner_event->getSubmittedLinksForDeletion();
    }

    #[\Override]
    public function setDeletedLinksAreNotValidWithMessage(string $message): void
    {
        $this->inner_event->setIsNotValid();
        $this->inner_event->setErrorMessage($message);
    }
}
