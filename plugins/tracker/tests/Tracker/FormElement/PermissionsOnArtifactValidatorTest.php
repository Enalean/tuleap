<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use ProjectUGroup;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use TuleapTestCase;

require_once __DIR__.'/../../bootstrap.php';

class PermissionsOnArtifactValidatorTest extends TuleapTestCase
{
    /** @var \Tracker_FormElement_Field_PermissionsOnArtifact */
    private $field;

    /** @var PermissionsOnArtifactValidator */
    private $validator;
    public function setUp()
    {
        parent::setUp();

        $this->field     = mock('Tracker_FormElement_Field_PermissionsOnArtifact');
        $this->validator = new PermissionsOnArtifactValidator();
    }

    public function itReturnsFalseNoUgroupsSet()
    {
        $value = array();

        $this->assertFalse(
            $this->validator->hasAGroupSelected($value)
        );
    }

    public function itReturnsTrueWhenUgroupsSet()
    {
        stub($this->field)->isRequired()->returns(true);
        $value['u_groups'] = array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);

        $this->assertTrue(
            $this->validator->hasAGroupSelected($value)
        );
    }

    public function itReturnsTrueWhenNoneIsSelected()
    {
        $value['u_groups'] = array(ProjectUGroup::NONE);

        $this->assertTrue(
            $this->validator->isNoneGroupSelected($value)
        );
    }

    public function itReturnsFalseWhenPermissionsAreNotSent()
    {
        $value = array();

        $this->assertFalse($this->validator->isArtifactPermissionChecked($value));
    }

    public function itReturnsFalseWhenPermissionsAreNotChecked()
    {
        $value = array(
            Tracker_FormElement_Field_PermissionsOnArtifact::USE_IT => 0
        );

        $this->assertFalse($this->validator->isArtifactPermissionChecked($value));
    }

    public function itReturnsTrueWhenPermissionsAreSentAndChecked()
    {
        $value = array(
            Tracker_FormElement_Field_PermissionsOnArtifact::USE_IT => 1
        );

        $this->assertTrue($this->validator->isArtifactPermissionChecked($value));
    }
}
