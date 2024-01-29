<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class FineGrainedRegexpValidatorTest extends TestIntegrationTestCase
{
    public function testValidatesPattern(): void
    {
        $pattern_01 = '*';
        $pattern_02 = '/*';
        $pattern_03 = 'master';
        $pattern_04 = 'master*';
        $pattern_05 = 'master/*';
        $pattern_06 = 'master/*/*';
        $pattern_07 = 'master/dev';
        $pattern_08 = 'master/dev*';
        $pattern_09 = 'master*/dev';
        $pattern_10 = '';
        $pattern_11 = 'master*[dev';
        $pattern_12 = 'master dev';
        $pattern_13 = 'master?dev';

        $pattern_14 = "master\n";
        $pattern_15 = "master\r";
        $pattern_16 = "master\n\r";
        $pattern_17 = "master\ndev";
        $pattern_18 = "\n";
        $pattern_19 = "\v";
        $pattern_20 = "\f";
        $pattern_21 = 'master\norms';

        $pattern_22 = 'refs/heads/^(?!master)$';

        $validator = new FineGrainedRegexpValidator();

        $this->assertTrue($validator->isPatternValid($pattern_01));
        $this->assertTrue($validator->isPatternValid($pattern_02));
        $this->assertTrue($validator->isPatternValid($pattern_03));
        $this->assertTrue($validator->isPatternValid($pattern_04));
        $this->assertTrue($validator->isPatternValid($pattern_05));
        $this->assertTrue($validator->isPatternValid($pattern_06));
        $this->assertTrue($validator->isPatternValid($pattern_07));
        $this->assertTrue($validator->isPatternValid($pattern_08));
        $this->assertTrue($validator->isPatternValid($pattern_09));
        $this->assertFalse($validator->isPatternValid($pattern_10));
        $this->assertTrue($validator->isPatternValid($pattern_11));
        $this->assertFalse($validator->isPatternValid($pattern_12));
        $this->assertTrue($validator->isPatternValid($pattern_13));

        $this->assertFalse($validator->isPatternValid($pattern_14));
        $this->assertFalse($validator->isPatternValid($pattern_15));
        $this->assertFalse($validator->isPatternValid($pattern_16));
        $this->assertFalse($validator->isPatternValid($pattern_17));
        $this->assertFalse($validator->isPatternValid($pattern_18));
        $this->assertFalse($validator->isPatternValid($pattern_19));
        $this->assertFalse($validator->isPatternValid($pattern_20));
        $this->assertTrue($validator->isPatternValid($pattern_21));

        $this->assertFalse($validator->isPatternValid($pattern_22));
    }
}
