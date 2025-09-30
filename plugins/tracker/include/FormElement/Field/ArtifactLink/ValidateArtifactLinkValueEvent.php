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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Artifact\Artifact;

class ValidateArtifactLinkValueEvent implements Dispatchable
{
    public const NAME = 'validateArtifactLinkValueEvent';

    private bool $is_valid        = true;
    private string $error_message = '';

    /**
     * @param int[] $submitted_links_for_deletion
     *
     * @psalm-param list<array<int, string>> $submitted_links_with_types
     */
    private function __construct(
        private Artifact $artifact,
        private array $submitted_links_for_deletion,
        private array $submitted_links_with_types,
    ) {
    }

    public static function buildFromSubmittedValues(Artifact $artifact, array $submitted_values): self
    {
        $submitted_links_for_deletion = [];
        if (array_key_exists('removed_values', $submitted_values) && ! empty($submitted_values['removed_values'])) {
            $submitted_links_for_deletion = array_keys($submitted_values['removed_values']);
        }

        $submitted_links_with_types = [];
        if (array_key_exists('types', $submitted_values) && ! empty($submitted_values['types'])) {
            $submitted_links_with_types = $submitted_values['types'];
        }

        return new self(
            $artifact,
            $submitted_links_for_deletion,
            $submitted_links_with_types,
        );
    }

    public function isValid(): bool
    {
        return $this->is_valid;
    }

    public function setIsNotValid(): void
    {
        $this->is_valid = false;
    }

    public function setErrorMessage(string $error_message): void
    {
        $this->error_message = $error_message;
    }

    public function getErrorMessage(): string
    {
        return $this->error_message;
    }

    /**
     * @return int[]
     */
    public function getSubmittedLinksForDeletion(): array
    {
        return $this->submitted_links_for_deletion;
    }

    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    /**
     * @psalm-return list<array<int, string>>
     */
    public function getSubmittedLinksWithTypes(): array
    {
        return $this->submitted_links_with_types;
    }
}
