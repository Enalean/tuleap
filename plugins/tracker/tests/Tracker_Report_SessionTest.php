<?php

require_once('bootstrap.php');
//Mock::generatePartial('Tracker_Report_Session', 'Mock_Tracker_Report_Session', array('getSessionNamespace') );


class Tracker_Report_SessionTest extends UnitTestCase {

    protected $report_id;
    protected $tracker_report_session;

    public function setUp() {
        $this->report_id = 111;
        //$this->tracker_report_session = new Mock_Tracker_Report_Session($this);
        $this->tracker_report_session = new Tracker_Report_Session($this->report_id);
        $_SESSION['Tracker_Report_SessionTest'] = array();
        $this->tracker_report_session->setSessionNamespace($_SESSION['Tracker_Report_SessionTest']);
        //$this->tracker_report_session->setReturnReference('getSessionNamespace', $_SESSION['Tracker_Report_SessionTest']['trackers']['reports']['report_'.$this->report_id]);
    }

    public function tearDown() {
        unset($_SESSION['Tracker_Report_SessionTest']);
        unset($this->tracker_report_session);
    }
    
    public function test_removeCriterion() {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session['criteria']['1'] = array('tintinlachipo');
        $this->tracker_report_session->removeCriterion('1');
        $this->assertEqual( $session['criteria']['1']['is_removed'], 1 );
    }

    public function test_removeCriterion_notFound() {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session['criteria']['1'] = 'tintinlachipo';
        $this->tracker_report_session->removeCriterion('0');
        $this->assertTrue( $session['criteria']['1'] === 'tintinlachipo');
    }

    public function test_storeCriterion_noOpts() {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $this->tracker_report_session->storeCriterion('4', array('tintin'=>'lachipo', 'kiki'=>'labrouette') );        
        $this->assertTrue(isset($session['criteria']['4']['value']));
        $this->assertEqual($session['criteria']['4']['value'], array('tintin'=>'lachipo', 'kiki'=>'labrouette'));
        $this->assertEqual($session['criteria']['4']['is_removed'], 0);
    }

    public function test_storeCriterion_withOpts() {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $this->tracker_report_session->storeCriterion('4', array('tintin'=>'lachipo', 'kiki'=>'labrouette') , $opts=array('is_advanced'=>1));
        $this->assertTrue(isset($session['criteria']['4']['value']));
        $this->assertEqual($session['criteria']['4']['value'], array('tintin'=>'lachipo', 'kiki'=>'labrouette'));
        $this->assertTrue(isset($session['criteria']['4']['is_advanced']));
        $this->assertEqual($session['criteria']['4']['is_advanced'], 1 );
        $this->assertEqual($session['criteria']['4']['is_removed'], 0);
    }

    public function test_getCriterion() {
         $session = &$this->tracker_report_session->getSessionNamespace();
         $session['criteria']['1'] = 'tintinlachipo';
         $criterion = $this->tracker_report_session->getCriterion(1);
         $this->assertEqual($criterion, 'tintinlachipo');
    }

    public function test_updateCriterion_emptyValue_emptyOpts() {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session[1]['value']       = 'tutu';
        $session[1]['is_advanced'] = 1;
        $criterion = $this->tracker_report_session->updateCriterion( 1, '');
        $this->assertTrue( $session[1]['value'], 'tutu');
        $this->assertTrue( $session[1]['is_advanced'], 1);
    }
    
    public function test_updateCriterion_emptyValue_withOpts() {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session[1]['value']       = 'tutu';
        $session[1]['is_advanced'] = 1;
        $criterion = $this->tracker_report_session->updateCriterion( 1, '', array('is_advanced', 0));
        $this->assertTrue( $session[1]['value'], 'tutu');
        $this->assertTrue( $session[1]['is_advanced'], 0);	
    }

    public function test_changeSessionNamespace_Relative_DoesntExist() {
         $session = &$this->tracker_report_session->getSessionNamespace(); 
         //$session = array ('fifi'=>array('riri'=>array('loulou'=>array ('oncle' => 'picsou'))));
         $this->tracker_report_session->changeSessionNamespace('fifi.riri');
         $new_session = $this->tracker_report_session->getSessionNamespace();
         $this->tracker_report_session->changeSessionNamespace('.Tracker_Report_SessionTest');
         $new_session = $this->tracker_report_session->getSessionNamespace();         
         $this->assertEqual($new_session, array('fifi'=>array('riri'=>array())));
    }

    public function test_updateCriterion_withValue_withOpts() {
        $session = &$this->tracker_report_session->getSessionNamespace();
        $session[1]['value']       = 'tutu';
        $session[1]['is_advanced'] = 1;
        $criterion = $this->tracker_report_session->updateCriterion( 1, 'toto', array('is_advanced', 0));
        $this->assertTrue( $session[1]['value'], 'toto');
        $this->assertTrue( $session[1]['is_advanced'], 0);
    }

    public function test_copy_NewRenderers() {
        $this->tracker_report_session->changeSessionNamespace('.');
        $session   = &$this->tracker_report_session->getSessionNamespace();
        $charts    = array('1'=>array('titi','toto','tata'), '2'=>array('titi','toto','tata') );
        $renderers = array('0'=>array('id'=>0, 'charts'=>$charts), '1'=>array('id'=>1, 'charts'=>$charts), '-3'=>array('id'=>-3, 'charts'=>$charts));
        $session   = array('trackers'=>array('reports'=>array('1'=>array('renderers'=> $renderers ))));
        $this->tracker_report_session->copy(1,2);        

        $this->assertEqual( count($_SESSION['trackers']['reports']['1']['renderers']), count($_SESSION['trackers']['reports']['2']['renderers']));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-1]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-2]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-1]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-2]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-1]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-2]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-1]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-2]) );
    }

     public function test_copy_RenderersWithNewCharts() {
        $this->tracker_report_session->changeSessionNamespace('.');
        $session   = &$this->tracker_report_session->getSessionNamespace();
        $charts    = array('2'=>array('titi','toto','tata'), '-1'=>array('titi','toto','tata') );
        $renderers = array('4'=>array('id'=>4, 'charts'=>$charts), '5'=>array('id'=>5, 'charts'=>$charts), '3'=>array('id'=>3, 'charts'=>$charts));
        $session   = array('trackers'=>array('reports'=>array('1'=>array('renderers'=> $renderers ))));
        $this->tracker_report_session->copy(1,2);
        $this->assertEqual( count($_SESSION['trackers']['reports']['1']['renderers']), count($_SESSION['trackers']['reports']['2']['renderers']));
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-1]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-2]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-1]['charts'][-1]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-1]['charts'][-2]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-2]['charts'][-1]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-2]['charts'][-2]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-1]) );
        $this->assertTrue(isset($_SESSION['trackers']['reports']['2']['renderers'][-3]['charts'][-2]) );
     }
}

?>
