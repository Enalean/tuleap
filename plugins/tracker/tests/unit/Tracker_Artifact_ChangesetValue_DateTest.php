<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_ChangesetValue_DateTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    use GlobalLanguageMock;

    private Tracker_Artifact_Changeset $changeset;
    private DateField $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field     = DateFieldBuilder::aDateField(541)->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(956)->build();
    }

    public function testDates(): void
    {
        $format = 'd/m/Y';
        $GLOBALS['Language']->method('getText')->willReturnCallback(static function () use (&$format) {
            return $format;
        });
        $date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        self::assertEquals(1221221466, $date->getTimestamp());
        self::assertEquals('12/09/2008', $date->getDate());

        $format = 'Y-m-d';
        $date   = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221467);
        self::assertEquals(1221221467, $date->getTimestamp());
        self::assertEquals('2008-09-12', $date->getDate());

        self::assertEquals('2008-09-12', $date->getValue());

        $null_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        self::assertNull($null_date->getTimestamp());
        self::assertEquals('', $null_date->getDate());
    }

    public function testNoDiff(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('Y-m-d');
        $date_1 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $date_2 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        self::assertFalse($date_1->diff($date_2));
        self::assertFalse($date_2->diff($date_1));
    }

    public function testDiffBetween2dates(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('Y-m-d');
        $date_1 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $date_2 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        self::assertEquals('changed from 2009-02-14 to 2008-09-12', $date_1->diff($date_2));
        self::assertEquals('changed from 2008-09-12 to 2009-02-14', $date_2->diff($date_1));
    }

    public function testDiffDateSet(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('Y-m-d');
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 0);
        $new_date      = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        self::assertEquals('set to 2009-02-14', $new_date->diff($previous_date));
    }

    public function testDiffDateCleared(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('Y-m-d');
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $new_date      = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        self::assertEquals('cleared', $new_date->diff($previous_date));
    }

    public function testDiffDateDidNotChanged(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('Y-m-d');
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $new_date      = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        self::assertEquals('', $new_date->diff($previous_date));
    }

    public function testDiffNoValueSubmittedYetBothDatesAreNull(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('Y-m-d');
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $new_date      = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        self::assertEquals('', $new_date->diff($previous_date));
    }
}
