<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/constants.php';
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

    public function test($compare)
    {
        return $compare instanceof LDAPResult && $compare->getCommonName() === $this->expected;
    }

    public function testMessage($compare)
    {
        if (! $compare instanceof LDAPResult) {
            return 'Expected a LDAPResult object, got ' . gettype($compare);
        }
        return "Expected {$this->expected}, recieved: {$compare->getCommonName()}";
    }
}
