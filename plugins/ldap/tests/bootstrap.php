<?php

require_once dirname(__FILE__).'/../include/autoload.php';

function aLDAPResultIterator() {
    return new LDAPResultIterator_BuilderForTest();
}

class LDAPResultIterator_BuilderForTest {

    private $info = array('count' => 0);
    private $params;

    public function withInfo(array $info) {
        $this->info = array(
            'count' => count($info),
        );
        $i = 0;
        foreach ($info as $people) {
            $nb_params_excluding_dn = count($people) - 1;
            $this->info[$i] = array(
                'dn'    => $people['dn'],
                'count' => $nb_params_excluding_dn
            );
            $j = 0;
            foreach ($people as $param => $value) {
                if ($param == 'dn') {
                    continue;
                }
                $this->info[$i][$param] = array(
                    'count' => 1,
                    0       => $value,
                );
                $this->info[$i][$j] = $param;
                $j++;
            }
            $i++;
        }
        return $this;
    }

    public function withParams(array $params) {
        $this->params = $params;
        return $this;
    }

    public function build() {
        return new LDAPResultIterator($this->info, $this->params);
    }
}

class LDAPResultExpectation extends SimpleExpectation {

    private $expected;

    public function __construct($expected_cn) {
        parent::__construct();
        $this->expected = $expected_cn;
    }

    public function test(LDAPResult $compare) {
        return $compare->getCommonName() == $this->expected;
    }

    public function testMessage(LDAPResult $compare) {
        return "Expected {$this->expected}, recieved: {$compare->getCommonName()}";
    }
}