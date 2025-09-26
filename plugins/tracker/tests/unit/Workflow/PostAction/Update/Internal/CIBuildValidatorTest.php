<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CIBuildValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CIBuildValueValidator $ci_build_validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->ci_build_validator = new CIBuildValueValidator();
    }

    public function testValidateDoesNotThrowWhenValid(): void
    {
        $first_ci_build  = new CIBuildValue('https://example.com');
        $second_ci_build = new CIBuildValue('https://example.com/2');

        $this->ci_build_validator->validate($first_ci_build, $second_ci_build);

        $this->expectNotToPerformAssertions();
    }

    public function testValidateThrowsWhenInvalidJobUrl(): void
    {
        $invalid_ci_build = new CIBuildValue('not an URL');

        $this->expectException(InvalidPostActionException::class);

        $this->ci_build_validator->validate($invalid_ci_build);
    }

    public function testValidateThrowsWhenEmptyJobUrl(): void
    {
        $invalid_ci_build = new CIBuildValue('');

        $this->expectException(InvalidPostActionException::class);

        $this->ci_build_validator->validate($invalid_ci_build);
    }
}
