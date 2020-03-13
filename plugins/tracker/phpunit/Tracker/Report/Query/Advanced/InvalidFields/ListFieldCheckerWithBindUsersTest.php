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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_Checkbox;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use UserManager;

require_once __DIR__ . '/../../../../../bootstrap.php';

class ListFieldCheckerWithBindUsersTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->current_user = new PFUser([
            'language_id' => 'en',
            'user_name' => 'admin'
        ]);
        $this->user_manager = \Mockery::spy(\UserManager::class);

        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter::class);
        $ugroup_label_converter->shouldReceive('isASupportedDynamicUgroup')->andReturns(false);

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

        $this->comparison = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison::class);
        $this->bind       = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Users::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->field      = $this->buildCheckboxField();

        $value_101 = new Tracker_FormElement_Field_List_Bind_UsersValue(101, 'admin');
        $value_103 = new Tracker_FormElement_Field_List_Bind_UsersValue(101, 'mandrew');

        $list_values      = array(
            101 => $value_101,
            103 => $value_103
        );

        $this->bind->shouldReceive('getAllValues')->andReturns($list_values);
    }

    private function buildCheckboxField(): Tracker_FormElement_Field_Checkbox
    {
        $field =  new Tracker_FormElement_Field_Checkbox(
            1,
            101,
            null,
            'checkbox',
            'Checkbox',
            null,
            true,
            null,
            null,
            null,
            null,
            null
        );

        $field->setBind($this->bind);

        return $field;
    }

    public function testItDoesNotThrowWhenEmptyValueIsAllowed(): void
    {
        $value_wrapper = new SimpleValueWrapper('');

        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->doesNotPerformAssertions();
    }

    public function testItDoesNotThrowWhenValueExists(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->current_user);
        $value_wrapper = new SimpleValueWrapper('admin');

        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->doesNotPerformAssertions();
    }

    public function testItDoesNotThrowWithMyselfValueAndCurrentUserIsLoggedIn(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->current_user);
        $value_wrapper = new CurrentUserValueWrapper($this->user_manager);

        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->doesNotPerformAssertions();
    }

    public function testItThrowsWithMyselfValueAndCurrentUserIsAnonymous(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns(null);
        $value_wrapper = new CurrentUserValueWrapper($this->user_manager);

        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->expectException(\Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToMySelfForAnonymousComparisonException::class);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function testItThrowsWhenValueDoesNotExist(): void
    {
        $value_wrapper = new SimpleValueWrapper('c');

        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->expectException(\Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException::class);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }
}
