<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;

/**
 * I hold the value of the Description semantic field of the source Timebox.
 * I am the reference value that must be copied to Mirrored Timeboxes.
 * @psalm-immutable
 */
final class DescriptionValue
{
    private string $value;
    private string $format;

    private function __construct(string $value, string $format)
    {
        $this->value  = $value;
        $this->format = $format;
    }

    /**
     * @throws ChangesetValueNotFoundException
     */
    public static function fromSynchronizedFields(
        RetrieveDescriptionValue $description_retriever,
        SynchronizedFieldReferences $fields
    ): self {
        $text_value = $description_retriever->getDescriptionValue($fields->description);
        return new self($text_value->getValue(), $text_value->getFormat());
    }

    /**
     * @return array{content: string, format: string}
     */
    public function getValue(): array
    {
        return [
            'content' => $this->value,
            'format'  => $this->format
        ];
    }
}
