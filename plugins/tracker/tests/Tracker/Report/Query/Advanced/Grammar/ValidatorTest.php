<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use TuleapTestCase;
use UserManager;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class ValidatorTest extends TuleapTestCase
{

    private $tracker;
    private $field;
    private $formelement_factory;
    private $validator;
    private $user;

    public function setUp()
    {
        parent::setUp();

        $this->tracker             = aTracker()->withId(101)->build();
        $this->field               = aStringField()->build();
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->user                = aUser()->build();

        $this->validator = new Validator($this->formelement_factory);
    }

    public function itDoesNotThrowAnExceptionIfFieldIsUsed()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns($this->field);

        $expr = new Comparison("field", "=", "value");

        $this->validator->validate($this->user, $this->tracker, $expr);
    }

    public function itThrowsAnExceptionIfFieldIsUnknown()
    {
        stub($this->formelement_factory)->getUsedFieldByNameForUser(101, "field", $this->user)->returns(null);

        $expr = new Comparison("field", "=", "value");

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\Grammar\FieldDoesNotExistException');
        $this->validator->validate($this->user, $this->tracker, $expr);
    }
}
