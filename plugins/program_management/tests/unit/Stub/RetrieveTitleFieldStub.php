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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveTitleField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldHasIncorrectTypeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;

final class RetrieveTitleFieldStub implements RetrieveTitleField
{
    private function __construct(private TitleFieldReference $title, private bool $has_error)
    {
    }

    public static function withField(int $field_id, string $field_label): self
    {
        return new self(self::getFieldReference($field_id, $field_label), false);
    }

    public static function withError(): self
    {
        return new self(self::getFieldReference(1, "My field"), true);
    }

    protected static function getFieldReference(int $field_id, string $field_label): TitleFieldReferenceProxy
    {
        return TitleFieldReferenceProxy::fromTrackerField(
            new \Tracker_FormElement_Field_String(
                $field_id,
                1,
                null,
                'irrelevant',
                $field_label,
                'Irrelevant',
                true,
                'P',
                true,
                '',
                1
            )
        );
    }

    public function getTitleField(TrackerIdentifier $tracker_identifier): TitleFieldReference
    {
        if ($this->has_error) {
            throw new TitleFieldHasIncorrectTypeException($tracker_identifier->getId(), 1);
        }

        return $this->title;
    }
}
