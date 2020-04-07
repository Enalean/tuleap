<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
final class ArtifactRulesManagerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    public function testValidate(): void
    {
        /*
        Fields:
        F1(A1, A2)
        F2(B1, B2, B3)
        F3(C1, C2)
        F4(D1, D2)

        Rules:
        F1(A1) => F2(B1, B3) The resource A1 can be used in rooms B1 and B3
        F1(A2) => F2(B2, B3) The resource A2 can be used in rooms B2 and B3
        F3(C1) => F2(B1, B3) The person C1 can access to rooms B1 and B3
        F3(C2) => F2(B2)     The person C2 can access to room B2 only

        /!\ those rules are not right since a field can be a target for only one field.

        Scenarios:
        S1 => A2, B3, C1, D1 should be valid (C1 can use A2 in B3)
        S2 => A2, B3, C2, D1 should *not* be valid (C2 cannot access to B3)
        S3 => (A1, A2), B3, C1, D1 should be valid
        S4 => (A1, A2), B2, C2, D1 should be valid (even if A1 cannot access to B2)
        S5 => A1, (B1, B3), C1, D1 should be valid
        S6 => A1, (B1, B2), C1, D1 should *not* be valid (A1 or C1 cannot access to B2)
        */
        $r1 = new ArtifactRuleValue(1, 1, 'F1', 'A1', 'F2', 'B1');
        $r2 = new ArtifactRuleValue(2, 1, 'F1', 'A1', 'F2', 'B3');
        $r3 = new ArtifactRuleValue(3, 1, 'F1', 'A2', 'F2', 'B2');
        $r4 = new ArtifactRuleValue(4, 1, 'F1', 'A2', 'F2', 'B3');
        $r5 = new ArtifactRuleValue(5, 1, 'F3', 'C1', 'F2', 'B1');
        $r6 = new ArtifactRuleValue(6, 1, 'F3', 'C1', 'F2', 'B3');
        $r7 = new ArtifactRuleValue(7, 1, 'F3', 'C2', 'F2', 'B2');

        $arf = \Mockery::spy(\ArtifactRuleFactory::class);
        $arf->shouldReceive('getAllRulesByArtifactTypeWithOrder')->andReturns(array(&$r1, &$r2, &$r3, &$r4, &$r5, &$r6, &$r7));

        $f1 = \Mockery::spy(\ArtifactField::class);
        $f1->shouldReceive('getID')->andReturns('F1');
        $f1->shouldReceive('getLabel')->andReturns('f_1');
        $f1->shouldReceive('getFieldPredefinedValues')->andReturns(null);

        $f2 = \Mockery::spy(\ArtifactField::class);
        $f2->shouldReceive('getID')->andReturns('F2');
        $f2->shouldReceive('getLabel')->andReturns('f_2');
        $f2->shouldReceive('getFieldPredefinedValues')->andReturns(null);

        $f3 = \Mockery::spy(\ArtifactField::class);
        $f3->shouldReceive('getID')->andReturns('F3');
        $f3->shouldReceive('getLabel')->andReturns('f_3');
        $f3->shouldReceive('getFieldPredefinedValues')->andReturns(null);

        $f4 = \Mockery::spy(\ArtifactField::class);
        $f4->shouldReceive('getID')->andReturns('F4');
        $f4->shouldReceive('getLabel')->andReturns('f_4');
        $f4->shouldReceive('getFieldPredefinedValues')->andReturns(null);

        $aff = \Mockery::spy(\ArtifactFieldFactory::class);
        $aff->shouldReceive('getFieldFromName')->with('f_1')->andReturns($f1);
        $aff->shouldReceive('getFieldFromName')->with('f_2')->andReturns($f2);
        $aff->shouldReceive('getFieldFromName')->with('f_3')->andReturns($f3);
        $aff->shouldReceive('getFieldFromName')->with('f_4')->andReturns($f4);

        $arm = \Mockery::mock(\ArtifactRulesManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $arm->shouldReceive('_getArtifactRuleFactory')->andReturns($arf);
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F1', 'A1')->andReturns(array('a_1'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F1', 'A2')->andReturns(array('a_2'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F2', 'B1')->andReturns(array('b_1'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F2', 'B2')->andReturns(array('b_2'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F2', 'B3')->andReturns(array('b_3'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F3', 'C1')->andReturns(array('c_1'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F3', 'C2')->andReturns(array('c_2'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F1', array('A1'))->andReturns(array('a_1'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F1', array('A2'))->andReturns(array('a_2'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F2', array('B1'))->andReturns(array('b_1'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F2', array('B2'))->andReturns(array('b_2'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F2', array('B3'))->andReturns(array('b_3'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F3', array('C1'))->andReturns(array('c_1'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F3', array('C2'))->andReturns(array('c_2'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F3', array('A1', 'A2'))->andReturns(array('a_1', 'a_2'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F3', array('B1', 'B3'))->andReturns(array('b_1', 'b_3'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F3', array('B1', 'B2'))->andReturns(array('b_1', 'b_2'));
        $arm->shouldReceive('_getSelectedValuesForField')->with(null, 'F3', array('B2', 'B3'))->andReturns(array('b_2', 'b_3'));

        //S1
        $this->assertTrue(
            $arm->validate(
                1,
                array(
                    'f_1' => 'A2',
                    'f_2' => 'B3',
                    'f_3' => 'C1',
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'], '');
        //S2
        $this->assertFalse(
            $arm->validate(
                1,
                array(
                    'f_1' => 'A2',
                    'f_2' => 'B3',
                    'f_3' => 'C2', //C2 cannot access to B3 !
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  'f_3(c_2) -> f_2(b_3) : ');
        //S3
        $this->assertTrue(
            $arm->validate(
                1,
                array(
                    'f_1' => array('A1', 'A2'),
                    'f_2' => 'B3',
                    'f_3' => 'C1',
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
        //S4
        $GLOBALS['Response'] = \Mockery::spy(\Response::class);
        $this->assertTrue(
            $arm->validate(
                1,
                array(
                    'f_1' => array('A1', 'A2'),
                    'f_2' => 'B2',
                    'f_3' => 'C2',
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
        //S5
        $GLOBALS['Response'] = \Mockery::spy(\Response::class);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('error', 'f_3(c_2) -> f_2(b_3)')->never();
        $this->assertTrue(
            $arm->validate(
                1,
                array(
                    'f_1' => 'A1',
                    'f_2' => array('B1', 'B3'),
                    'f_3' => 'C1',
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
        //S6
        $GLOBALS['Response'] = \Mockery::spy(\Response::class);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('error', 'f_1(a_1) -> f_2(b_2)')->once();
        $this->assertFalse(
            $arm->validate(
                1,
                array(
                    'f_1' => 'A1',
                    'f_2' => array('B1', 'B2'), //A1 cannot access to B2 !
                    'f_3' => 'C1',
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  'f_1(a_1) -> f_2(b_2)');
    }

    public function testForbidden(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = \Mockery::spy(\ArtifactRuleFactory::class);
        $arf->shouldReceive('getAllRulesByArtifactTypeWithOrder')->andReturns(array($r1, $r2, $r3));

        $arm = \Mockery::mock(\ArtifactRulesManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $arm->shouldReceive('_getArtifactRuleFactory')->andReturns($arf);

        //Forbidden sources
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'A', 'A'), "Field A cannot be the source of field A");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'B', 'A'), "Field B cannot be the source of field A because A->B->A is cyclic");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'A'), "Field C cannot be the source of field A because A->B->C->A is cyclic");
        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'D', 'A'), "Field D can be the source of field A");

        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'A', 'B'), "Field A is the source of field B");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'B', 'B'), "Field B cannot be the source of field B");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'B'), "Field C cannot be the source of field B because B is already a target");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'B'), "Field D cannot be the source of field B because B is already a target");

        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'A', 'C'), "Field A cannot be the source of field C because C is already a target");
        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'B', 'C'), "Field B is the source of field C");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'C'), "Field C cannot be the source of field C");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'C'), "Field D cannot be the source of field C because C is already a target");

        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'A', 'D'), "Field A can be the source of field D");
        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'B', 'D'), "Field B can be the source of field D");
        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'C', 'D'), "Field C can be the source of field D");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'D'), "Field D cannot be the source of field D");

        //Forbidden targets
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'A'), "Field A cannot be the target of field A");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'B', 'A'), "Field B is the target of field A");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'A'), "Field C cannot be the target of field A because C is already a target");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'A'), "Field D can be the target of field A");

        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'B'), "Field A cannot be the target of field B because A->B->A is cyclic");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'B'), "Field B cannot be the target of field B");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'C', 'B'), "Field C is the target of field B");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'B'), "Field D can be the target of field B");

        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'C'), "Field A cannot be the target of field C because A->B->C->A is cyclic");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'C'), "Field B cannot be the target of field C because B is already a target");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'C'), "Field C cannot be the target of field C");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'C'), "Field D can be the target of field C");

        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'A', 'D'), "Field A can be the target of field D");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'D'), "Field B cannot be the target of field D because B is already a target");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'D'), "Field C cannot be the target of field D because C is already a target");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'D', 'D'), "Field D cannot be the target of field D");
    }

    public function testFieldHasSourceTarget(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = \Mockery::spy(\ArtifactRuleFactory::class);
        $arf->shouldReceive('getAllRulesByArtifactTypeWithOrder')->andReturns(array($r1, $r2, $r3));

        $arm = \Mockery::mock(\ArtifactRulesManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $arm->shouldReceive('_getArtifactRuleFactory')->andReturns($arf);

        $this->assertFalse($arm->fieldHasSource(1, 'A'));
        $this->assertTrue($arm->fieldHasSource(1, 'B'));
        $this->assertTrue($arm->fieldHasSource(1, 'C'));
        $this->assertFalse($arm->fieldHasSource(1, 'D'));
        $this->assertTrue($arm->fieldHasSource(1, 'E'));
        $this->assertFalse($arm->fieldHasSource(1, 'F'));

        $this->assertTrue($arm->fieldHasTarget(1, 'A'));
        $this->assertTrue($arm->fieldHasTarget(1, 'B'));
        $this->assertFalse($arm->fieldHasTarget(1, 'C'));
        $this->assertTrue($arm->fieldHasTarget(1, 'D'));
        $this->assertFalse($arm->fieldHasTarget(1, 'E'));
        $this->assertFalse($arm->fieldHasTarget(1, 'F'));
    }

    public function testIsCyclic(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = \Mockery::spy(\ArtifactRuleFactory::class);
        $arf->shouldReceive('getAllRulesByArtifactTypeWithOrder')->andReturns(array($r1, $r2, $r3));

        $arm = \Mockery::mock(\ArtifactRulesManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $arm->shouldReceive('_getArtifactRuleFactory')->andReturns($arf);

        $this->assertTrue($arm->isCyclic(1, 'A', 'A'));
        $this->assertFalse($arm->isCyclic(1, 'A', 'B'));
        $this->assertFalse($arm->isCyclic(1, 'A', 'C'));
        $this->assertFalse($arm->isCyclic(1, 'A', 'D'));
        $this->assertFalse($arm->isCyclic(1, 'A', 'E'));

        $this->assertTrue($arm->isCyclic(1, 'B', 'A'));
        $this->assertTrue($arm->isCyclic(1, 'B', 'B'));
        $this->assertFalse($arm->isCyclic(1, 'B', 'C'));
        $this->assertFalse($arm->isCyclic(1, 'B', 'D'));
        $this->assertFalse($arm->isCyclic(1, 'B', 'E'));

        $this->assertTrue($arm->isCyclic(1, 'C', 'A'));
        $this->assertTrue($arm->isCyclic(1, 'C', 'B'));
        $this->assertTrue($arm->isCyclic(1, 'C', 'C'));
        $this->assertFalse($arm->isCyclic(1, 'C', 'D'));
        $this->assertFalse($arm->isCyclic(1, 'C', 'E'));

        $this->assertFalse($arm->isCyclic(1, 'D', 'A'));
        $this->assertFalse($arm->isCyclic(1, 'D', 'B'));
        $this->assertFalse($arm->isCyclic(1, 'D', 'C'));
        $this->assertTrue($arm->isCyclic(1, 'D', 'D'));
        $this->assertFalse($arm->isCyclic(1, 'D', 'E'));

        $this->assertFalse($arm->isCyclic(1, 'E', 'A'));
        $this->assertFalse($arm->isCyclic(1, 'E', 'B'));
        $this->assertFalse($arm->isCyclic(1, 'E', 'C'));
        $this->assertTrue($arm->isCyclic(1, 'E', 'D'));
        $this->assertTrue($arm->isCyclic(1, 'E', 'E'));
    }

    public function testRuleExists(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = \Mockery::spy(\ArtifactRuleFactory::class);
        $arf->shouldReceive('getAllRulesByArtifactTypeWithOrder')->andReturns(array($r1, $r2, $r3));

        $arm = \Mockery::mock(\ArtifactRulesManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $arm->shouldReceive('_getArtifactRuleFactory')->andReturns($arf);

        //Rule exists
        $this->assertFalse($arm->ruleExists(1, 'A', 'A'));
        $this->assertTrue($arm->ruleExists(1, 'A', 'B'));
        $this->assertFalse($arm->ruleExists(1, 'A', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'A', 'D'));
        $this->assertFalse($arm->ruleExists(1, 'A', 'E'));

        $this->assertFalse($arm->ruleExists(1, 'B', 'A'));
        $this->assertFalse($arm->ruleExists(1, 'B', 'B'));
        $this->assertTrue($arm->ruleExists(1, 'B', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'B', 'D'));
        $this->assertFalse($arm->ruleExists(1, 'B', 'E'));

        $this->assertFalse($arm->ruleExists(1, 'C', 'A'));
        $this->assertFalse($arm->ruleExists(1, 'C', 'B'));
        $this->assertFalse($arm->ruleExists(1, 'C', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'C', 'D'));
        $this->assertFalse($arm->ruleExists(1, 'C', 'E'));

        $this->assertFalse($arm->ruleExists(1, 'D', 'A'));
        $this->assertFalse($arm->ruleExists(1, 'D', 'B'));
        $this->assertFalse($arm->ruleExists(1, 'D', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'D', 'D'));
        $this->assertTrue($arm->ruleExists(1, 'D', 'E'));

        $this->assertFalse($arm->ruleExists(1, 'E', 'A'));
        $this->assertFalse($arm->ruleExists(1, 'E', 'B'));
        $this->assertFalse($arm->ruleExists(1, 'E', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'E', 'D'));
        $this->assertFalse($arm->ruleExists(1, 'E', 'E'));
    }

    public function testValueHasSourceTarget(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = \Mockery::spy(\ArtifactRuleFactory::class);
        $arf->shouldReceive('getAllRulesByArtifactTypeWithOrder')->andReturns(array($r1, $r2, $r3));

        $arm = \Mockery::mock(\ArtifactRulesManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $arm->shouldReceive('_getArtifactRuleFactory')->andReturns($arf);

        //value has source or target
        $this->assertTrue($arm->valueHasSource(1, 'B', 2, 'A'));
        $this->assertFalse($arm->valueHasSource(1, 'B', 2, 'C'));
        $this->assertFalse($arm->valueHasSource(1, 'B', 3, 'C'));
        $this->assertTrue($arm->valueHasTarget(1, 'B', 3, 'C'));
        $this->assertFalse($arm->valueHasTarget(1, 'B', 3, 'A'));
        $this->assertFalse($arm->valueHasTarget(1, 'B', 2, 'A'));
    }
}
