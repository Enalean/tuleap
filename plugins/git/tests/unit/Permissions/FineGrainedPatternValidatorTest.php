<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FineGrainedPatternValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestWith(['*'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master/*'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master/dev'])]
    public function testValidPatterns(string $pattern): void
    {
        $validator = new FineGrainedPatternValidator();

        self::assertTrue($validator->isPatternValid($pattern));
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['/*'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master*'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master/*/*'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master/dev*'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master*/dev'])]
    #[\PHPUnit\Framework\Attributes\TestWith([''])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master*[dev'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master dev'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['master?dev'])]
    #[\PHPUnit\Framework\Attributes\TestWith(["master\n"])]
    #[\PHPUnit\Framework\Attributes\TestWith(["master\r"])]
    #[\PHPUnit\Framework\Attributes\TestWith(["master\n\r"])]
    #[\PHPUnit\Framework\Attributes\TestWith(["master\ndev"])]
    #[\PHPUnit\Framework\Attributes\TestWith(["\n"])]
    #[\PHPUnit\Framework\Attributes\TestWith(["\f"])]
    public function testInvalidPatterns(string $pattern): void
    {
        $validator = new FineGrainedPatternValidator();

        self::assertFalse($validator->isPatternValid($pattern));
    }

    public function testVerticalTab(): void
    {
        $validator = new FineGrainedPatternValidator();

        $this->assertFalse($validator->isPatternValid("\v"));
    }
}
