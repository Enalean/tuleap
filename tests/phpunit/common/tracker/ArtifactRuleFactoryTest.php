<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ArtifactRuleFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGetRuleById(): void
    {
        $rules_dar             = \Mockery::spy(\DataAccessResult::class);
        $rules_dar->shouldReceive('getRow')->andReturns(array(
            'id'                => 123,
            'group_artifact_id' => 1,
            'source_field_id'   => 2,
            'source_value_id'   => 10,
            'target_field_id'   => 4,
            'rule_type'         => 4, //RuleValue
            'target_value_id'   => 100
        ));

        $rules_dao             = \Mockery::spy(\ArtifactRuleDao::class);
        $rules_dao->shouldReceive('searchById')->with(123)->andReturns($rules_dar);

        $arf = new ArtifactRuleFactory($rules_dao);

        $r = $arf->getRuleById(123);
        $this->assertInstanceOf(\ArtifactRule::class, $r);
        $this->assertInstanceOf(\ArtifactRuleValue::class, $r);
        $this->assertEquals(123, $r->id);
        $this->assertEquals(2, $r->source_field);
        $this->assertEquals(4, $r->target_field);
        $this->assertEquals(10, $r->source_value);
        $this->assertEquals(100, $r->target_value);

        $this->assertNull($arf->getRuleById(124), 'If id is inexistant, then return will be null');

        $this->assertSame($r, $arf->getRuleById(123), 'We do not create two different instances for the same id');
    }
}
