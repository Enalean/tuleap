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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DurationFieldReferenceProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int FIELD_ID       = 797;
    private const string FIELD_LABEL = 'voraciousness';

    public function testItBuildsFromIntegerField(): void
    {
        $field = DurationFieldReferenceProxy::fromTrackerField($this->getIntegerField());
        self::assertSame(self::FIELD_ID, $field->getId());
        self::assertSame(self::FIELD_LABEL, $field->getLabel());
    }

    public function testItBuildsFromFloatField(): void
    {
        $field = DurationFieldReferenceProxy::fromTrackerField($this->getFloatField());
        self::assertSame(self::FIELD_ID, $field->getId());
        self::assertSame(self::FIELD_LABEL, $field->getLabel());
    }

    private function getIntegerField(): \Tuleap\Tracker\FormElement\Field\Integer\IntegerField
    {
        return new \Tuleap\Tracker\FormElement\Field\Integer\IntegerField(
            self::FIELD_ID,
            68,
            1,
            'irrelevant',
            self::FIELD_LABEL,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }

    private function getFloatField(): \Tuleap\Tracker\FormElement\Field\Float\FloatField
    {
        return new \Tuleap\Tracker\FormElement\Field\Float\FloatField(
            self::FIELD_ID,
            68,
            1,
            'irrelevant',
            self::FIELD_LABEL,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }
}
