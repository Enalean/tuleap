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

namespace Tuleap\Tracker\Artifact;

/**
 * @psalm-immutable
 */
final readonly class RichTextareaConfiguration
{
    private function __construct(
        public \Tuleap\Tracker\Tracker $tracker,
        public ?Artifact $artifact,
        public \PFUser $user,
        public string $id,
        public string $name,
        public int $number_of_rows,
        public int $number_of_columns,
        public string $content,
        public bool $is_required,
    ) {
    }

    public static function fromNewFollowUpComment(
        \Tuleap\Tracker\Tracker $tracker,
        Artifact $artifact,
        \PFUser $user,
        string $comment,
    ): self {
        return new self(
            $tracker,
            $artifact,
            $user,
            'tracker_followup_comment_new',
            'artifact_followup_comment',
            8,
            80,
            $comment,
            false
        );
    }

    public static function fromTextField(
        \Tuleap\Tracker\Tracker $tracker,
        ?Artifact $artifact,
        \PFUser $user,
        \Tuleap\Tracker\FormElement\Field\Text\TextField $field,
        string $content,
    ): self {
        return new self(
            $tracker,
            $artifact,
            $user,
            'field_' . $field->getId(),
            'artifact[' . $field->getId() . '][content]',
            (int) $field->getProperty('rows'),
            (int) $field->getProperty('cols'),
            $content,
            $field->isRequired()
        );
    }
}
