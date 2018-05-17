<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once __DIR__.'/../bootstrap.php';

class TrackerRestBuilderTest extends TuleapTestCase {

    private $formelement_factory;
    private $builder;
    private $tracker;
    private $user;
    private $semantic_manager;

    public function setUp() {
        parent::setUp();
        $this->formelement_factory   = mock('Tracker_FormElementFactory');
        $this->tracker               = mock('Tracker');
        $this->user                  = mock('PFUser');
        $this->semantic_manager      = mock('Tracker_SemanticManager');
        $this->builder               = partial_mock('Tracker_REST_TrackerRestBuilder', array('getSemanticManager'), array($this->formelement_factory));
        $this->workflow              = mock('Workflow');

        stub($this->builder)->getSemanticManager()->returns($this->semantic_manager);
    }

    public function itReturnsAnArrayEvenWhenFieldsAreNotReadable() {
        stub($this->semantic_manager)->exportToREST()->returns(array());

        $field1 = aMockField()->withId(1)->build();
        stub($field1)->userCanRead()->returns(true);
        stub($field1)->exportCurrentUserPermissionsToSOAP()->returns(array());
        stub($field1)->getSoapBindingProperties()->returns(aStringField()->build()->getSoapBindingProperties());
        $field2 = aMockField()->withId(2)->build();
        stub($field2)->userCanRead()->returns(false);
        stub($field2)->exportCurrentUserPermissionsToSOAP()->returns(array());
        stub($field2)->getSoapBindingProperties()->returns(aStringField()->build()->getSoapBindingProperties());
        $field3 = aMockField()->withId(3)->build();
        stub($field3)->userCanRead()->returns(true);
        stub($field3)->exportCurrentUserPermissionsToSOAP()->returns(array());
        stub($field3)->getSoapBindingProperties()->returns(aStringField()->build()->getSoapBindingProperties());

        stub($this->workflow)->getField()->returns($field2);
        stub($this->tracker)->getWorkflow()->returns($this->workflow);
        stub($this->formelement_factory)->getUsedFields()->returns(array($field1, $field2, $field3));
        stub($this->formelement_factory)->getAllUsedFormElementOfAnyTypesForTracker()->returns(array($field1, $field2, $field3));
        stub($this->formelement_factory)->getType()->returns('string');

        $tracker_representation = $this->builder->getTrackerRepresentation($this->user, $this->tracker);
        $this->assertEqual(array_keys($tracker_representation->fields), array(0, 1));
    }
}
