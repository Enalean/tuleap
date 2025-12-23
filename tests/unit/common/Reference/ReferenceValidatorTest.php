<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the tereference_validators of the GNU General Public License as published by
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

namespace Tuleap\Reference;

use ReferenceDao;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceValidatorTest extends TestCase
{
    private ReferenceValidator $reference_validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->reference_validator = new ReferenceValidator(
            $this->createMock(ReferenceDao::class),
            new ReservedKeywordsRetriever(EventDispatcherStub::withIdentityCallback())
        );
    }

    public function testItTestKeywordCharacterValidation(): void
    {
        self::assertFalse($this->reference_validator->isValidKeyword('UPPER'));
        self::assertFalse($this->reference_validator->isValidKeyword('with space'));
        self::assertFalse($this->reference_validator->isValidKeyword('with$pecialchar'));
        self::assertFalse($this->reference_validator->isValidKeyword('with/special/char'));
        self::assertFalse($this->reference_validator->isValidKeyword('with-special'));
        self::assertFalse($this->reference_validator->isValidKeyword('-begin'));
        self::assertFalse($this->reference_validator->isValidKeyword('end-'));
        self::assertFalse($this->reference_validator->isValidKeyword('end '));

        self::assertTrue($this->reference_validator->isValidKeyword('valid'));
        self::assertTrue($this->reference_validator->isValidKeyword('valid123'));
        self::assertTrue($this->reference_validator->isValidKeyword('123'));
        self::assertTrue($this->reference_validator->isValidKeyword('with_underscore'));
    }

    public function testItTestIfKeywordIsReserved(): void
    {
        self::assertTrue($this->reference_validator->isReservedKeyword('project'));
        self::assertFalse($this->reference_validator->isReservedKeyword('john2'));
    }
}
