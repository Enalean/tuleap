<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use TuleapTestCase;

require_once __DIR__.'/../../../../../bootstrap.php';

class NatureValidatorTest extends TuleapTestCase
{

    private $expected_exception = 'Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\InvalidNatureParameterException';
    private $validator;
    private $dao;

    public function setUp()
    {
        parent::setUp();

        $this->dao = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao');

        $this->validator = new NatureValidator($this->dao);
    }

    public function itThrowsAnExceptionIfShortnameDoesNotRespectFormat()
    {
        $this->expectException($this->expected_exception);

        $this->validator->checkShortname("_fixed_in");
    }

    public function itThrowsAnExceptionIfShortnameIsEmpty()
    {
        $this->expectException($this->expected_exception);

        $this->validator->checkShortname("");
    }

    public function itThrowsAnExceptionIfForwardLabelIsEmpty()
    {
        $this->expectException($this->expected_exception);

        $this->validator->checkForwardLabel("");
    }

    public function itThrowsAnExceptionIfSReverseLabelIsEmpty()
    {
        $this->expectException($this->expected_exception);

        $this->validator->checkReverseLabel("");
    }

    public function itDoesNotComplainIfShortnameIsValid()
    {
        $this->validator->checkShortname("fixed_in");
    }

    public function itDoesNothComplainIfForwardLabelIsValid()
    {
        $this->validator->checkForwardLabel("Fixed In");
    }

    public function itDoesNothComplainIfReverseLabelIsValid()
    {
        $this->validator->checkReverseLabel("Fixed");
    }

    public function itThrowsAnExceptionIfNatureIsAlreadyUsed()
    {
        stub($this->dao)->isOrHasBeenUsed()->returns(true);

        $this->expectException('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\UnableToDeleteNatureException');

        $this->validator->checkIsNotOrHasNotBeenUsed('_fixed_in');
    }
}
