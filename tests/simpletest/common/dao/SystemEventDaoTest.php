<?php
require_once('common/dao/SystemEventDao.class.php');
require_once('common/dao/include/DataAccess.class.php');

class SystemEventDao_SearchWithParamTest extends TuleapTestCase {
    
    private $da;

    private $search_term = 'abc';
    private $event_type  = array('MY_IMAGINARY_EVENT');
    private $status      = array('ONGOING');

    public function setUp() {
        parent::setUp();

        $this->da = partial_mock('DataAccess', array('quoteSmart', 'quoteSmartImplode'));
        stub($this->da)->quoteSmart($this->search_term.SystemEvent::PARAMETER_SEPARATOR . '%')->returns("'" . $this->search_term . "%'");
        stub($this->da)->quoteSmart('%' . SystemEvent::PARAMETER_SEPARATOR.$this->search_term)->returns("'%" . $this->search_term . "'");
        stub($this->da)->quoteSmart($this->search_term)->returns($this->search_term);

        stub($this->da)->quoteSmartImplode(', ', $this->event_type)->returns('MY_IMAGINARY_EVENT');
        stub($this->da)->quoteSmartImplode(', ', $this->status)->returns('ONGOING');
    }

    public function itCreatesCorrectQueryWithSearchTermInFirstPosition() {
        $this->dao = partial_mock('SystemEventDao', array('retrieve'), array($this->da));

        $expected_sql = "SELECT  * FROM system_event
                WHERE type   IN (MY_IMAGINARY_EVENT)
                AND status IN (ONGOING)
                AND parameters LIKE 'abc%'";

        stub($this->dao)->retrieve($expected_sql)->once();

        $this->dao->searchWithParam('head', $this->search_term, $this->event_type, $this->status);
     }

    public function itCreatesCorrectQueryWithSearchTermInLastPosition() {
        $this->dao = partial_mock('SystemEventDao', array('retrieve'), array($this->da));

        $expected_sql = "SELECT  * FROM system_event
                WHERE type   IN (MY_IMAGINARY_EVENT)
                AND status IN (ONGOING)
                AND parameters LIKE '%abc'";

        stub($this->dao)->retrieve($expected_sql)->once();

        $this->dao->searchWithParam('tail', $this->search_term, $this->event_type, $this->status);
     }

    public function itCreatesCorrectQueryWithExactSearchTerm() {
        $this->dao = partial_mock('SystemEventDao', array('retrieve'), array($this->da));

        $expected_sql = 'SELECT  * FROM system_event
                WHERE type   IN (MY_IMAGINARY_EVENT)
                AND status IN (ONGOING)
                AND parameters LIKE abc';

        stub($this->dao)->retrieve($expected_sql)->once();

        $this->dao->searchWithParam('all', $this->search_term, $this->event_type, $this->status);
     }
}

