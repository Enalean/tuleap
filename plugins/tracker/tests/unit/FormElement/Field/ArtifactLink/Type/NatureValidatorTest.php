<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class NatureValidatorTest extends TestCase
{
    private TypeValidator $validator;
    private TypeDao&MockObject $dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao       = $this->createMock(TypeDao::class);
        $this->validator = new TypeValidator($this->dao);
    }

    public function testItThrowsAnExceptionIfShortnameDoesNotRespectFormat(): void
    {
        $this->expectException(InvalidTypeParameterException::class);

        $this->validator->checkShortname('_fixed_in');
    }

    public function testItThrowsAnExceptionIfShortnameIsEmpty(): void
    {
        $this->expectException(InvalidTypeParameterException::class);

        $this->validator->checkShortname('');
    }

    public function testItThrowsAnExceptionIfForwardLabelIsEmpty(): void
    {
        $this->expectException(InvalidTypeParameterException::class);

        $this->validator->checkForwardLabel('');
    }

    public function testItThrowsAnExceptionIfSReverseLabelIsEmpty(): void
    {
        $this->expectException(InvalidTypeParameterException::class);

        $this->validator->checkReverseLabel('');
    }

    public function testItDoesNotComplainIfShortnameIsValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->checkShortname('fixed_in');
    }

    public function testItDoesNothComplainIfForwardLabelIsValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->checkForwardLabel('Fixed In');
    }

    public function testItDoesNothComplainIfReverseLabelIsValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->checkReverseLabel('Fixed');
    }

    public function testItThrowsAnExceptionIfNatureIsAlreadyUsed(): void
    {
        $this->dao->method('isOrHasBeenUsed')->willReturn(true);

        $this->expectException(UnableToDeleteTypeException::class);

        $this->validator->checkIsNotOrHasNotBeenUsed('_fixed_in');
    }
}
