<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

Mock::generate('ArtifactRuleDao');
Mock::generate('DataAccessResult');
class ArtifactRuleFactoryTest extends TuleapTestCase
{

    function testGetRuleById()
    {

        $rules_dar             = new MockDataAccessResult($this);
        $rules_dar->setReturnValue('getRow', array(
            'id'                => 123,
            'group_artifact_id' => 1,
            'source_field_id'   => 2,
            'source_value_id'   => 10,
            'target_field_id'   => 4,
            'rule_type'         => 4, //RuleValue
            'target_value_id'   => 100
        ));

        $rules_dao             = new MockArtifactRuleDao($this);
        $rules_dao->setReturnReference('searchById', $rules_dar, array(123));

        $arf = new ArtifactRuleFactory($rules_dao);

        $r = $arf->getRuleById(123);
        $this->assertIsA($r, 'ArtifactRule');
        $this->assertIsA($r, 'ArtifactRuleValue');
        $this->assertEqual($r->id, 123);
        $this->assertEqual($r->source_field, 2);
        $this->assertEqual($r->target_field, 4);
        $this->assertEqual($r->source_value, 10);
        $this->assertEqual($r->target_value, 100);

        $this->assertFalse($arf->getRuleById(124), 'If id is inexistant, then return will be false');

        $this->assertReference($arf->getRuleById(123), $r, 'We do not create two different instances for the same id');
    }
}
