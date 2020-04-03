<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
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

final class Tracker_FormElement_Field_SubmittedOnTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testhasChanges(): void
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_SubmittedOn::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $v = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Date::class);
        $this->assertFalse($f->hasChanges(\Mockery::spy(\Tracker_Artifact::class), $v, null));
    }

    public function testisValid(): void
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_SubmittedOn::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $a = \Mockery::spy(\Tracker_Artifact::class);
        $this->assertTrue($f->isValid($a, null));
    }
}
