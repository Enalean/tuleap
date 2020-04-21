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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

final class NatureValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var NatureValidator
     */
    private $validator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|NatureDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao::class);

        $this->validator = new NatureValidator($this->dao);
    }

    public function testItThrowsAnExceptionIfShortnameDoesNotRespectFormat(): void
    {
        $this->expectException(InvalidNatureParameterException::class);

        $this->validator->checkShortname("_fixed_in");
    }

    public function testItThrowsAnExceptionIfShortnameIsEmpty(): void
    {
        $this->expectException(InvalidNatureParameterException::class);

        $this->validator->checkShortname("");
    }

    public function testItThrowsAnExceptionIfForwardLabelIsEmpty(): void
    {
        $this->expectException(InvalidNatureParameterException::class);

        $this->validator->checkForwardLabel("");
    }

    public function testItThrowsAnExceptionIfSReverseLabelIsEmpty(): void
    {
        $this->expectException(InvalidNatureParameterException::class);

        $this->validator->checkReverseLabel("");
    }

    public function testItDoesNotComplainIfShortnameIsValid(): void
    {
        $this->validator->checkShortname("fixed_in");
        $this->addToAssertionCount(1);
    }

    public function testItDoesNothComplainIfForwardLabelIsValid(): void
    {
        $this->validator->checkForwardLabel("Fixed In");
        $this->addToAssertionCount(1);
    }

    public function testItDoesNothComplainIfReverseLabelIsValid(): void
    {
        $this->validator->checkReverseLabel("Fixed");
        $this->addToAssertionCount(1);
    }

    public function testItThrowsAnExceptionIfNatureIsAlreadyUsed(): void
    {
        $this->dao->shouldReceive('isOrHasBeenUsed')->andReturns(true);

        $this->expectException(UnableToDeleteNatureException::class);

        $this->validator->checkIsNotOrHasNotBeenUsed('_fixed_in');
    }
}
