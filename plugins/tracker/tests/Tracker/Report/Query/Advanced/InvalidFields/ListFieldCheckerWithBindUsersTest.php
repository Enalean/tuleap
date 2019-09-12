<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use PFUser;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use TuleapTestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use UserManager;

require_once __DIR__.'/../../../../../bootstrap.php';

class ListFieldCheckerWithBindUsersTest extends TuleapTestCase
{
    /** @var ListFieldChecker */
    private $list_field_checker;
    /** @var Tracker_FormElement_Field_List */
    private $field;
    /** @var Comparison */
    private $comparison;
    /** @var Tracker_FormElement_Field_List_Bind_Users */
    private $bind;
    /** @var UserManager */
    private $user_manager;
    /** @var  PFUser */
    private $current_user;

    public function setUp()
    {
        parent::setUp();

        $this->current_user = aUser()->withId(101)->withUserName('admin')->build();
        $this->user_manager = mock('UserManager');

        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = mock('Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter');
        stub($ugroup_label_converter)->isASupportedDynamicUgroup()->returns(false);

        $this->list_field_checker = new ListFieldChecker(
            new EmptyStringAllowed(),
            new CollectionOfListValuesExtractor(),
            $list_field_bind_value_normalizer,
            new CollectionOfNormalizedBindLabelsExtractor(
                $list_field_bind_value_normalizer,
                $ugroup_label_converter
            ),
            $ugroup_label_converter
        );

        $this->comparison = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $this->bind       = partial_mock('Tracker_FormElement_Field_List_Bind_Users', array(
            'getAllValues'
        ));
        $this->field      = aCheckboxField()->withBind($this->bind)->build();
        $list_values      = array(
            101 => aBindUsersValue()->withId(101)->withUserName('admin')->build(),
            103 => aBindUsersValue()->withId(103)->withUserName('mandrew')->build()
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
        stub($this->user_manager)->getCurrentUser()->returns($this->current_user);
        $value_wrapper = new SimpleValueWrapper('admin');

        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->pass();
    }

    public function itDoesNotThrowWithMyselfValueAndCurrentUserIsLoggedIn()
    {
        stub($this->user_manager)->getCurrentUser()->returns($this->current_user);
        $value_wrapper = new CurrentUserValueWrapper($this->user_manager);

        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->pass();
    }

    public function itThrowsWithMyselfValueAndCurrentUserIsAnonymous()
    {
        stub($this->user_manager)->getCurrentUser()->returns(null);
        $value_wrapper = new CurrentUserValueWrapper($this->user_manager);

        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToMySelfForAnonymousComparisonException');

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function itThrowsWhenValueDoesNotExist()
    {
        $value_wrapper = new SimpleValueWrapper('c');

        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException');

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }
}
