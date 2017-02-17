<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields;

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\BindStaticLabelExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\BindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use TuleapTestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class ListFieldCheckerTest extends TuleapTestCase
{
    /** @var ListFieldChecker */
    private $list_field_checker;
    /** @var Tracker_FormElement_Field_List */
    private $field;
    /** @var Comparison */
    private $comparison;
    /** @var Tracker_FormElement_Field_List_Bind_Static */
    private $bind;

    public function setUp()
    {
        parent::setUp();

        $this->list_field_checker = new ListFieldChecker(
            new EmptyStringAllowed(),
            new CollectionOfListValuesExtractor(),
            new BindValueNormalizer(),
            new CollectionOfNormalizedBindLabelsExtractor(
                new BindValueNormalizer()
            )
        );
        $this->comparison = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $this->bind       = partial_mock('Tracker_FormElement_Field_List_Bind_Static', array(
            'getAllValues'
        ));
        $this->field              = aCheckboxField()->withBind($this->bind)->build();
        $list_values              = array(
            100 => aFieldListStaticValue()->withId(100)->withLabel('a')->build(),
            101 => aFieldListStaticValue()->withId(101)->withLabel('b')->build()
        );
        stub($this->bind)->getAllValues()->returns($list_values);
    }

    public function itDoesNotThrowWhenEmptyValueIsAllowed()
    {
        $value_wrapper = new SimpleValueWrapper('');

        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->pass();
    }

    public function itDoesNotThrowWhenValueExists()
    {
        $value_wrapper = new SimpleValueWrapper('a');

        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->pass();
    }

    public function itThrowsWhenValueDoNotExist()
    {
        $value_wrapper = new SimpleValueWrapper('c');

        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException');

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }
}
