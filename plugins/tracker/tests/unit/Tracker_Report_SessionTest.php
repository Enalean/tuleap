<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Report_SessionTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $tracker_report_session;

    protected function setUp(): void
    {
        $report_id                              = 111;
        $this->tracker_report_session           = new Tracker_Report_Session($report_id);
        $_SESSION['Tracker_Report_SessionTest'] = array();
        $this->tracker_report_session->setSessionNamespace($_SESSION['Tracker_Report_SessionTest']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testRemovesCriterion(): void
    {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session['criteria']['1'] = array('tintinlachipo');
        $this->tracker_report_session->removeCriterion('1');
        $this->assertEquals(1, $session['criteria']['1']['is_removed']);
    }

    public function testRemovesCriterionNotFound(): void
    {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session['criteria']['1'] = 'tintinlachipo';
        $this->tracker_report_session->removeCriterion('0');
        $this->assertSame('tintinlachipo', $session['criteria']['1']);
    }

    public function testStoreCriterionNoOpts(): void
    {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $this->tracker_report_session->storeCriterion('4', array('tintin' => 'lachipo', 'kiki' => 'labrouette'));
        $this->assertTrue(isset($session['criteria']['4']['value']));
        $this->assertEquals(array('tintin' => 'lachipo', 'kiki' => 'labrouette'), $session['criteria']['4']['value']);
        $this->assertEquals(0, $session['criteria']['4']['is_removed']);
    }

    public function testStoreCriterionWithOpts(): void
    {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $this->tracker_report_session->storeCriterion('4', array('tintin' => 'lachipo', 'kiki' => 'labrouette'), $opts = array('is_advanced' => 1));
        $this->assertTrue(isset($session['criteria']['4']['value']));
        $this->assertEquals(array('tintin' => 'lachipo', 'kiki' => 'labrouette'), $session['criteria']['4']['value']);
        $this->assertTrue(isset($session['criteria']['4']['is_advanced']));
        $this->assertEquals(1, $session['criteria']['4']['is_advanced']);
        $this->assertEquals(0, $session['criteria']['4']['is_removed']);
    }

    public function testItStoresAdditionalCriterion(): void
    {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $additional_criterion = new Tracker_Report_AdditionalCriterion('agiledashboard_milestone', array('tintin' => 'lachipo', 'kiki' => 'labrouette'));
        $this->tracker_report_session->storeAdditionalCriterion($additional_criterion);
        $this->assertEquals(
            $session['additional_criteria']['agiledashboard_milestone']['value'],
            $additional_criterion->getValue()
        );
    }

    public function testGetCriterion(): void
    {
         $session = &$this->tracker_report_session->getSessionNamespace();
         $session['criteria']['1'] = 'tintinlachipo';
         $criterion = $this->tracker_report_session->getCriterion(1);
         $this->assertEquals('tintinlachipo', $criterion);
    }

    public function testUpdatesCriterionEmptyValueEmptyOpts(): void
    {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session[1]['value']       = 'tutu';
        $session[1]['is_advanced'] = 1;
        $this->tracker_report_session->updateCriterion(1, '');
        $this->assertEquals('tutu', $session[1]['value']);
        $this->assertEquals(1, $session[1]['is_advanced']);
    }

    public function testUpdateCriterionEmptyValueWithOpts(): void
    {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session[1]['value']       = 'tutu';
        $session['criteria'][1]['is_advanced'] = 1;
        $this->tracker_report_session->updateCriterion(1, '', array('is_advanced' => 0));
        $this->assertEquals('tutu', $session[1]['value']);
        $this->assertEquals(0, $session['criteria'][1]['is_advanced']);
    }

    public function testChangeSessionNamespaceRelativeDoesntExist(): void
    {
         $this->tracker_report_session->getSessionNamespace();
         $this->tracker_report_session->changeSessionNamespace('fifi.riri');
         $this->tracker_report_session->getSessionNamespace();
         $this->tracker_report_session->changeSessionNamespace('.Tracker_Report_SessionTest');
         $new_session = $this->tracker_report_session->getSessionNamespace();
         $this->assertEquals(array('fifi' => array('riri' => array())), $new_session);
    }

    public function testUpdateCriterionWithValueWithOpts(): void
    {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session['criteria'][1]['value'] = 'tutu';
        $session['criteria'][1]['is_advanced'] = 1;
        $this->tracker_report_session->updateCriterion(1, 'toto', array('is_advanced' => 0));
        $this->assertEquals('toto', $session['criteria'][1]['value']);
        $this->assertEquals(0, $session['criteria'][1]['is_advanced']);
    }

    public function testCopyNewRenderers(): void
    {
        $this->tracker_report_session->changeSessionNamespace('.');
        $session   = &$this->tracker_report_session->getSessionNamespace();
        $charts    = array('1' => array('titi','toto','tata'), '2' => array('titi','toto','tata') );
        $renderers = array('0' => array('id' => 0, 'charts' => $charts), '1' => array('id' => 1, 'charts' => $charts), '-3' => array('id' => -3, 'charts' => $charts));
        $session   = array('trackers' => array('reports' => array('1' => array('renderers' => $renderers ))));
        $this->tracker_report_session->copy(1, 2);

        $this->assertEquals(count($_SESSION['trackers']['reports']['1']['renderers']), count($_SESSION['trackers']['reports']['2']['renderers']));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-1]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-2]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-1]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-2]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-1]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-2]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-1]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-2]));
    }

    public function testCopyRenderersWithNewCharts(): void
    {
        $this->tracker_report_session->changeSessionNamespace('.');
        $session   = &$this->tracker_report_session->getSessionNamespace();
        $charts    = array('2' => array('titi','toto','tata'), '-1' => array('titi','toto','tata') );
        $renderers = array('4' => array('id' => 4, 'charts' => $charts), '5' => array('id' => 5, 'charts' => $charts), '3' => array('id' => 3, 'charts' => $charts));
        $session   = array('trackers' => array('reports' => array('1' => array('renderers' => $renderers ))));
        $this->tracker_report_session->copy(1, 2);
        $this->assertEquals(count($_SESSION['trackers']['reports']['1']['renderers']), count($_SESSION['trackers']['reports']['2']['renderers']));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-1]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-2]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-1]['charts'][-1]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-1]['charts'][-2]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-2]['charts'][-1]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-2]['charts'][-2]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-1]));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-2]));
    }
}
