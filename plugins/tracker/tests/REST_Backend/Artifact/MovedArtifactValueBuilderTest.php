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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use TuleapTestCase;

require_once __DIR__.'/../../bootstrap.php';

class MovedArtifactValueBuilderTest extends TuleapTestCase
{
    /** @var  MovedArtifactValueBuilder */
    private $builder;

    /** @var  Tracker_Artifact */
    private $artifact;

    /** @var  Tracker */
    private $tracker;

    /** @var Tracker_FormElement_Field_String */
    private $field_string;

    public function setUp()
    {
        parent::setUp();

        $this->builder      = new MovedArtifactValueBuilder();
        $this->artifact     = mock('Tracker_Artifact');
        $this->tracker      = mock('Tracker');
        $this->field_string = stub('Tracker_FormElement_Field_String')->getId()->returns(101);
    }

    public function itThrowsAnExceptionIfArtifactHasNoTitle()
    {
        stub($this->artifact)->getTitle()->returns(null);
        stub($this->tracker)->getTitleField()->returns($this->field_string);

        $this->expectException('Tuleap\Tracker\Exception\SemanticTitleNotDefinedException');

        $this->builder->getValues($this->artifact, $this->tracker);
    }

    public function itThrowsAnExceptionIfTrackerHasNoTitle()
    {
        stub($this->artifact)->getTitle()->returns("title");
        stub($this->tracker)->getTitleField()->returns(null);

        $this->expectException('Tuleap\Tracker\Exception\SemanticTitleNotDefinedException');

        $this->builder->getValues($this->artifact, $this->tracker);
    }

    public function itBuildsArtifactValuesRepresentation()
    {
        stub($this->artifact)->getTitle()->returns("title");
        stub($this->tracker)->getTitleField()->returns($this->field_string);

        $values = $this->builder->getValues($this->artifact, $this->tracker);

        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = 101;
        $representation->value    = "title";

        $expected = array(
            $representation
        );

        $this->assertEqual($values, $expected);
    }
}
