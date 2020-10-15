<?php
/**
 * Copyright (c) Tuleap 2019-present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Test.
 *
 * Test is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Test is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Test. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Tracker\Artifact\Artifact;

final class Tracker_FormElement_Field_LastUpdateDateTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testHasChanges(): void
    {
        $f = $this->getLastUpdateDateField();
        $v = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $this->assertFalse($f->hasChanges(Mockery::mock(Artifact::class), $v, null));
    }

    public function testisValid(): void
    {
        $f = $this->getLastUpdateDateField();
        $a = Mockery::mock(Artifact::class);
        $this->assertTrue($f->isValid($a, null));
    }

    /**
     * @return \Mockery\Mock | Tracker_FormElement_Field_LastUpdateDate
     */
    protected function getLastUpdateDateField()
    {
        return Mockery::mock(Tracker_FormElement_Field_LastUpdateDate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }
}
