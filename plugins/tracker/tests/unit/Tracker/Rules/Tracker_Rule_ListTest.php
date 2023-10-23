<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Rule;

use Tracker_Rule_List;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Rule_ListTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testApplyTo()
    {
        $trv = new Tracker_Rule_List();
        $trv->setSourceValue('source_value')
            ->setTargetValue('target_value')
            ->setId('id')
            ->setTrackerId('tracker_id')
            ->setSourceFieldId('source_field')
            ->setTargetFieldId('target_field');
        $this->assertTrue($trv->applyTo('tracker_id', 'source_field', 'source_value', 'target_field', 'target_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        //$this->assertFalse($trv->applyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'target_value'      ));
        $this->assertFalse($trv->applyTo('false_tracker_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'target_source_field', 'source_value', 'target_field', 'false_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'target_source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'false_source_value', 'target_field', 'false_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'false_source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'source_value', 'false_target_field', 'false_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'source_value', 'false_target_field', 'false_target_value'));
    }

    public function testCanApplyTo()
    {
        $trv = new Tracker_Rule_List();
        $trv->setSourceValue('source_value')
            ->setTargetValue('target_value')
            ->setId('id')
            ->setTrackerId('tracker_id')
            ->setSourceFieldId('source_field')
            ->setTargetFieldId('target_field');
        $this->assertTrue($trv->canApplyTo('tracker_id', 'source_field', 'source_value', 'target_field', 'target_value'));
        $this->assertTrue($trv->canApplyTo('tracker_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        //$this->assertFalse($trv->canApplyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'target_value'      ));
        //$this->assertFalse($trv->canApplyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'target_source_field', 'source_value', 'target_field', 'false_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'target_source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'source_field', 'false_source_value', 'target_field', 'false_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'source_field', 'false_source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'source_field', 'source_value', 'false_target_field', 'false_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'source_field', 'source_value', 'false_target_field', 'false_target_value'));
    }
}
