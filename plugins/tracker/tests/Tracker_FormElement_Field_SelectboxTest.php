<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once('bootstrap.php');

class Tracker_FormElement_Field_Selectbox_getCurrentDecoratorColorTest extends TuleapTestCase {

    private $changeset_with_no_value_id = 1;
    private $changeset_with_value_id    = 2;

    private $one_value   = array(array('id' => 123));
    private $many_values = array(array('id' => 456), array('id' => 123));

    public function setUp() {
        $this->changeset_with_no_value = mock('Tracker_Artifact_Changeset');
        stub($this->changeset_with_no_value)->getId()->returns($this->changeset_with_no_value_id);

        $this->changeset_with_value = mock('Tracker_Artifact_Changeset');
        stub($this->changeset_with_value)->getId()->returns($this->changeset_with_value_id);


        $this->artifact_with_value = mock('Tracker_Artifact');
        stub($this->artifact_with_value)->getLastChangeset()->returns($this->changeset_with_value);

        $this->artifact_with_no_changeset = mock('Tracker_Artifact');

        $this->artifact_with_no_value = mock('Tracker_Artifact');
        stub($this->artifact_with_no_value)->getLastChangeset()->returns($this->changeset_with_no_value);

        $this->bind_with_no_decorator = mock('Tracker_FormElement_Field_List_Bind_Static');
        stub($this->bind_with_no_decorator)->getDecorators()->returns(array());

        $red   = stub('Tracker_FormElement_Field_List_BindDecorator')->css()->returns('red');
        $green = stub('Tracker_FormElement_Field_List_BindDecorator')->css()->returns('green');

        $this->bind_with_decorators = mock('Tracker_FormElement_Field_List_Bind_Static');
        stub($this->bind_with_decorators)->getDecorators()->returns(array(123 => $red, 456 => $green));
    }

    public function itReturnsRed() {
        stub($this->bind_with_decorators)->getChangesetValues($this->changeset_with_value_id)->returns($this->one_value);
        $selectbox = aSelectBoxField()->withBind($this->bind_with_decorators)->build();
        $this->assertEqual('red', $selectbox->getCurrentDecoratorColor($this->artifact_with_value));
    }

    public function itReturnsTheFirstColorFound() {
        stub($this->bind_with_decorators)->getChangesetValues($this->changeset_with_value_id)->returns($this->many_values);
        $selectbox = aSelectBoxField()->withBind($this->bind_with_decorators)->build();
        $this->assertEqual('green', $selectbox->getCurrentDecoratorColor($this->artifact_with_value));
    }

    public function itReturnsNullWhenThereIsNotAnyDecorator() {
        stub($this->bind_with_no_decorator)->getChangesetValues($this->changeset_with_value_id)->returns($this->one_value);
        $selectbox = aSelectBoxField()->withBind($this->bind_with_no_decorator)->build();
        $this->assertNull($selectbox->getCurrentDecoratorColor($this->artifact_with_value));
    }

    public function itReturnsNullWhenCurrentChangesetHasNoValue() {
        $bind = mock('Tracker_FormElement_Field_List_Bind_Static');
        $selectbox = aSelectBoxField()->withBind($bind)->build();
        $this->assertNull($selectbox->getCurrentDecoratorColor($this->artifact_with_no_value));
    }

    public function itReturnsNullWhenArtifactHasNoChangeset() {
        $selectbox = aSelectBoxField()->build();
        $this->assertNull($selectbox->getCurrentDecoratorColor($this->artifact_with_no_changeset));
    }
}
?>
