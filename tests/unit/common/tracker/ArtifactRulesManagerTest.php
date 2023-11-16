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
final class ArtifactRulesManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
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

        $arf = $this->createMock(\ArtifactRuleFactory::class);
        $arf->method('getAllRulesByArtifactTypeWithOrder')->willReturn([&$r1, &$r2, &$r3, &$r4, &$r5, &$r6, &$r7]);

        $f1 = $this->createMock(\ArtifactField::class);
        $f1->method('getID')->willReturn('F1');
        $f1->method('getLabel')->willReturn('f_1');
        $f1->method('getFieldPredefinedValues')->willReturn(null);

        $f2 = $this->createMock(\ArtifactField::class);
        $f2->method('getID')->willReturn('F2');
        $f2->method('getLabel')->willReturn('f_2');
        $f2->method('getFieldPredefinedValues')->willReturn(null);

        $f3 = $this->createMock(\ArtifactField::class);
        $f3->method('getID')->willReturn('F3');
        $f3->method('getLabel')->willReturn('f_3');
        $f3->method('getFieldPredefinedValues')->willReturn(null);

        $f4 = $this->createMock(\ArtifactField::class);
        $f4->method('getID')->willReturn('F4');
        $f4->method('getLabel')->willReturn('f_4');
        $f4->method('getFieldPredefinedValues')->willReturn(null);

        $aff = $this->createMock(\ArtifactFieldFactory::class);
        $aff->method('getFieldFromName')->willReturnMap([
            ['f_1', $f1],
            ['f_2', $f2],
            ['f_3', $f3],
            ['f_4', $f4],
        ]);

        $arm = $this->getMockBuilder(\ArtifactRulesManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_getArtifactRuleFactory', '_getSelectedValuesForField'])
            ->getMock();
        $arm->method('_getArtifactRuleFactory')->willReturn($arf);
        $arm->method('_getSelectedValuesForField')->willReturnCallback(
            function ($db_result, $field_id, $field_values): array {
                if ($db_result === null && $field_id === 'F1' && $field_values === 'A1') {
                    return ['a_1'];
                }
                if ($db_result === null && $field_id === 'F1' && $field_values === 'A2') {
                    return ['a_2'];
                }
                if ($db_result === null && $field_id === 'F2' && $field_values === 'B1') {
                    return ['b_1'];
                }
                if ($db_result === null && $field_id === 'F2' && $field_values === 'B2') {
                    return ['b_2'];
                }
                if ($db_result === null && $field_id === 'F2' && $field_values === 'B3') {
                    return ['b_3'];
                }
                if ($db_result === null && $field_id === 'F3' && $field_values === 'C1') {
                    return ['c_1'];
                }
                if ($db_result === null && $field_id === 'F3' && $field_values === 'C2') {
                    return ['c_2'];
                }
                if ($db_result === null && $field_id === 'F1' && $field_values === ['A1']) {
                    return ['a_1'];
                }
                if ($db_result === null && $field_id === 'F1' && $field_values === ['A2']) {
                    return ['a_2'];
                }
                if ($db_result === null && $field_id === 'F2' && $field_values === ['B1']) {
                    return ['b_1'];
                }
                if ($db_result === null && $field_id === 'F2' && $field_values === ['B2']) {
                    return ['b_2'];
                }
                if ($db_result === null && $field_id === 'F2' && $field_values === ['B3']) {
                    return ['b_3'];
                }
                if ($db_result === null && $field_id === 'F3' && $field_values === ['C1']) {
                    return ['c_1'];
                }
                if ($db_result === null && $field_id === 'F3' && $field_values === ['C2']) {
                    return ['c_2'];
                }
                if ($db_result === null && $field_id === 'F3' && $field_values === ['A1', 'A2']) {
                    return ['a_1', 'a_2'];
                }
                if ($db_result === null && $field_id === 'F3' && $field_values === ['B1', 'B3']) {
                    return ['b_1', 'b_3'];
                }
                if ($db_result === null && $field_id === 'F3' && $field_values === ['B1', 'B2']) {
                    return ['b_1', 'b_2'];
                }
                if ($db_result === null && $field_id === 'F3' && $field_values === ['B2', 'B3']) {
                    return ['b_2', 'b_3'];
                }

                throw new RuntimeException();
            }
        );

        //S1
        self::assertTrue(
            $arm->validate(
                1,
                [
                    'f_1' => 'A2',
                    'f_2' => 'B3',
                    'f_3' => 'C1',
                    'f_4' => 'D1',
                ],
                $aff
            )
        );
        //self::assertEqual($GLOBALS['feedback'], '');
        //S2
        self::assertFalse(
            $arm->validate(
                1,
                [
                    'f_1' => 'A2',
                    'f_2' => 'B3',
                    'f_3' => 'C2', //C2 cannot access to B3 !
                    'f_4' => 'D1',
                ],
                $aff
            )
        );
        //self::assertEqual($GLOBALS['feedback'],  'f_3(c_2) -> f_2(b_3) : ');
        //S3
        self::assertTrue(
            $arm->validate(
                1,
                [
                    'f_1' => ['A1', 'A2'],
                    'f_2' => 'B3',
                    'f_3' => 'C1',
                    'f_4' => 'D1',
                ],
                $aff
            )
        );
        //self::assertEqual($GLOBALS['feedback'],  '');
        //S4
        $GLOBALS['Response'] = $this->createMock(\Response::class);
        self::assertTrue(
            $arm->validate(
                1,
                [
                    'f_1' => ['A1', 'A2'],
                    'f_2' => 'B2',
                    'f_3' => 'C2',
                    'f_4' => 'D1',
                ],
                $aff
            )
        );
        //self::assertEqual($GLOBALS['feedback'],  '');
        //S5
        $GLOBALS['Response'] = $this->createMock(\Response::class);
        $GLOBALS['Response']->expects(self::never())->method('addFeedback')->with('error', 'f_3(c_2) -> f_2(b_3)');
        self::assertTrue(
            $arm->validate(
                1,
                [
                    'f_1' => 'A1',
                    'f_2' => ['B1', 'B3'],
                    'f_3' => 'C1',
                    'f_4' => 'D1',
                ],
                $aff
            )
        );
        //self::assertEqual($GLOBALS['feedback'],  '');
        //S6
        $GLOBALS['Response'] = $this->createMock(\Response::class);
        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error', 'f_1(a_1) -> f_2(b_2)');
        self::assertFalse(
            $arm->validate(
                1,
                [
                    'f_1' => 'A1',
                    'f_2' => ['B1', 'B2'], //A1 cannot access to B2 !
                    'f_3' => 'C1',
                    'f_4' => 'D1',
                ],
                $aff
            )
        );
        //self::assertEqual($GLOBALS['feedback'],  'f_1(a_1) -> f_2(b_2)');
    }

    public function testForbidden(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = $this->createMock(\ArtifactRuleFactory::class);
        $arf->method('getAllRulesByArtifactTypeWithOrder')->willReturn([$r1, $r2, $r3]);

        $arm = $this->getMockBuilder(\ArtifactRulesManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_getArtifactRuleFactory'])
            ->getMock();
        $arm->method('_getArtifactRuleFactory')->willReturn($arf);

        //Forbidden sources
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'A', 'A'), "Field A cannot be the source of field A");
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'B', 'A'), "Field B cannot be the source of field A because A->B->A is cyclic");
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'A'), "Field C cannot be the source of field A because A->B->C->A is cyclic");
        self::assertFalse($arm->fieldIsAForbiddenSource(1, 'D', 'A'), "Field D can be the source of field A");

        self::assertFalse($arm->fieldIsAForbiddenSource(1, 'A', 'B'), "Field A is the source of field B");
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'B', 'B'), "Field B cannot be the source of field B");
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'B'), "Field C cannot be the source of field B because B is already a target");
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'B'), "Field D cannot be the source of field B because B is already a target");

        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'A', 'C'), "Field A cannot be the source of field C because C is already a target");
        self::assertFalse($arm->fieldIsAForbiddenSource(1, 'B', 'C'), "Field B is the source of field C");
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'C'), "Field C cannot be the source of field C");
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'C'), "Field D cannot be the source of field C because C is already a target");

        self::assertFalse($arm->fieldIsAForbiddenSource(1, 'A', 'D'), "Field A can be the source of field D");
        self::assertFalse($arm->fieldIsAForbiddenSource(1, 'B', 'D'), "Field B can be the source of field D");
        self::assertFalse($arm->fieldIsAForbiddenSource(1, 'C', 'D'), "Field C can be the source of field D");
        self::assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'D'), "Field D cannot be the source of field D");

        //Forbidden targets
        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'A'), "Field A cannot be the target of field A");
        self::assertFalse($arm->fieldIsAForbiddenTarget(1, 'B', 'A'), "Field B is the target of field A");
        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'A'), "Field C cannot be the target of field A because C is already a target");
        self::assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'A'), "Field D can be the target of field A");

        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'B'), "Field A cannot be the target of field B because A->B->A is cyclic");
        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'B'), "Field B cannot be the target of field B");
        self::assertFalse($arm->fieldIsAForbiddenTarget(1, 'C', 'B'), "Field C is the target of field B");
        self::assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'B'), "Field D can be the target of field B");

        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'C'), "Field A cannot be the target of field C because A->B->C->A is cyclic");
        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'C'), "Field B cannot be the target of field C because B is already a target");
        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'C'), "Field C cannot be the target of field C");
        self::assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'C'), "Field D can be the target of field C");

        self::assertFalse($arm->fieldIsAForbiddenTarget(1, 'A', 'D'), "Field A can be the target of field D");
        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'D'), "Field B cannot be the target of field D because B is already a target");
        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'D'), "Field C cannot be the target of field D because C is already a target");
        self::assertTrue($arm->fieldIsAForbiddenTarget(1, 'D', 'D'), "Field D cannot be the target of field D");
    }

    public function testFieldHasSourceTarget(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = $this->createMock(\ArtifactRuleFactory::class);
        $arf->method('getAllRulesByArtifactTypeWithOrder')->willReturn([$r1, $r2, $r3]);

        $arm = $this->getMockBuilder(\ArtifactRulesManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_getArtifactRuleFactory'])
            ->getMock();
        $arm->method('_getArtifactRuleFactory')->willReturn($arf);

        self::assertFalse($arm->fieldHasSource(1, 'A'));
        self::assertTrue($arm->fieldHasSource(1, 'B'));
        self::assertTrue($arm->fieldHasSource(1, 'C'));
        self::assertFalse($arm->fieldHasSource(1, 'D'));
        self::assertTrue($arm->fieldHasSource(1, 'E'));
        self::assertFalse($arm->fieldHasSource(1, 'F'));

        self::assertTrue($arm->fieldHasTarget(1, 'A'));
        self::assertTrue($arm->fieldHasTarget(1, 'B'));
        self::assertFalse($arm->fieldHasTarget(1, 'C'));
        self::assertTrue($arm->fieldHasTarget(1, 'D'));
        self::assertFalse($arm->fieldHasTarget(1, 'E'));
        self::assertFalse($arm->fieldHasTarget(1, 'F'));
    }

    public function testIsCyclic(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = $this->createMock(\ArtifactRuleFactory::class);
        $arf->method('getAllRulesByArtifactTypeWithOrder')->willReturn([$r1, $r2, $r3]);

        $arm = $this->getMockBuilder(\ArtifactRulesManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_getArtifactRuleFactory'])
            ->getMock();
        $arm->method('_getArtifactRuleFactory')->willReturn($arf);

        self::assertTrue($arm->isCyclic(1, 'A', 'A'));
        self::assertFalse($arm->isCyclic(1, 'A', 'B'));
        self::assertFalse($arm->isCyclic(1, 'A', 'C'));
        self::assertFalse($arm->isCyclic(1, 'A', 'D'));
        self::assertFalse($arm->isCyclic(1, 'A', 'E'));

        self::assertTrue($arm->isCyclic(1, 'B', 'A'));
        self::assertTrue($arm->isCyclic(1, 'B', 'B'));
        self::assertFalse($arm->isCyclic(1, 'B', 'C'));
        self::assertFalse($arm->isCyclic(1, 'B', 'D'));
        self::assertFalse($arm->isCyclic(1, 'B', 'E'));

        self::assertTrue($arm->isCyclic(1, 'C', 'A'));
        self::assertTrue($arm->isCyclic(1, 'C', 'B'));
        self::assertTrue($arm->isCyclic(1, 'C', 'C'));
        self::assertFalse($arm->isCyclic(1, 'C', 'D'));
        self::assertFalse($arm->isCyclic(1, 'C', 'E'));

        self::assertFalse($arm->isCyclic(1, 'D', 'A'));
        self::assertFalse($arm->isCyclic(1, 'D', 'B'));
        self::assertFalse($arm->isCyclic(1, 'D', 'C'));
        self::assertTrue($arm->isCyclic(1, 'D', 'D'));
        self::assertFalse($arm->isCyclic(1, 'D', 'E'));

        self::assertFalse($arm->isCyclic(1, 'E', 'A'));
        self::assertFalse($arm->isCyclic(1, 'E', 'B'));
        self::assertFalse($arm->isCyclic(1, 'E', 'C'));
        self::assertTrue($arm->isCyclic(1, 'E', 'D'));
        self::assertTrue($arm->isCyclic(1, 'E', 'E'));
    }

    public function testRuleExists(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = $this->createMock(\ArtifactRuleFactory::class);
        $arf->method('getAllRulesByArtifactTypeWithOrder')->willReturn([$r1, $r2, $r3]);

        $arm = $this->getMockBuilder(\ArtifactRulesManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_getArtifactRuleFactory'])
            ->getMock();
        $arm->method('_getArtifactRuleFactory')->willReturn($arf);

        //Rule exists
        self::assertFalse($arm->ruleExists(1, 'A', 'A'));
        self::assertTrue($arm->ruleExists(1, 'A', 'B'));
        self::assertFalse($arm->ruleExists(1, 'A', 'C'));
        self::assertFalse($arm->ruleExists(1, 'A', 'D'));
        self::assertFalse($arm->ruleExists(1, 'A', 'E'));

        self::assertFalse($arm->ruleExists(1, 'B', 'A'));
        self::assertFalse($arm->ruleExists(1, 'B', 'B'));
        self::assertTrue($arm->ruleExists(1, 'B', 'C'));
        self::assertFalse($arm->ruleExists(1, 'B', 'D'));
        self::assertFalse($arm->ruleExists(1, 'B', 'E'));

        self::assertFalse($arm->ruleExists(1, 'C', 'A'));
        self::assertFalse($arm->ruleExists(1, 'C', 'B'));
        self::assertFalse($arm->ruleExists(1, 'C', 'C'));
        self::assertFalse($arm->ruleExists(1, 'C', 'D'));
        self::assertFalse($arm->ruleExists(1, 'C', 'E'));

        self::assertFalse($arm->ruleExists(1, 'D', 'A'));
        self::assertFalse($arm->ruleExists(1, 'D', 'B'));
        self::assertFalse($arm->ruleExists(1, 'D', 'C'));
        self::assertFalse($arm->ruleExists(1, 'D', 'D'));
        self::assertTrue($arm->ruleExists(1, 'D', 'E'));

        self::assertFalse($arm->ruleExists(1, 'E', 'A'));
        self::assertFalse($arm->ruleExists(1, 'E', 'B'));
        self::assertFalse($arm->ruleExists(1, 'E', 'C'));
        self::assertFalse($arm->ruleExists(1, 'E', 'D'));
        self::assertFalse($arm->ruleExists(1, 'E', 'E'));
    }

    public function testValueHasSourceTarget(): void
    {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');

        $arf = $this->createMock(\ArtifactRuleFactory::class);
        $arf->method('getAllRulesByArtifactTypeWithOrder')->willReturn([$r1, $r2, $r3]);

        $arm = $this->getMockBuilder(\ArtifactRulesManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_getArtifactRuleFactory'])
            ->getMock();
        $arm->method('_getArtifactRuleFactory')->willReturn($arf);

        //value has source or target
        self::assertTrue($arm->valueHasSource(1, 'B', 2, 'A'));
        self::assertFalse($arm->valueHasSource(1, 'B', 2, 'C'));
        self::assertFalse($arm->valueHasSource(1, 'B', 3, 'C'));
        self::assertTrue($arm->valueHasTarget(1, 'B', 3, 'C'));
        self::assertFalse($arm->valueHasTarget(1, 'B', 3, 'A'));
        self::assertFalse($arm->valueHasTarget(1, 'B', 2, 'A'));
    }
}
