<?php

/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

require_once 'bootstrap.php';

Mock::generatePartial('Docman_SqlFilter', 'Docman_SqlFilterTestVersion', array());

class SqlFilterChoiceTest extends TuleapTestCase {

    function testSqlFilterChoicePerPattern() {
        $docmanSf = new Docman_SqlFilterTestVersion($this);

        $this->assertEqual($docmanSf->getSearchType('*codex*')   , array('like' => true, 'pattern' =>'"%codex%"'));
        $this->assertEqual($docmanSf->getSearchType('*c*od*ex*') , array('like' => true, 'pattern' =>'"%c*od*ex%"'));
        $this->assertEqual($docmanSf->getSearchType('*codex')    , array('like' => true, 'pattern' =>'"%codex"'));
        $this->assertEqual($docmanSf->getSearchType('**codex')   , array('like' => true, 'pattern' =>'"%*codex"'));
        $this->assertEqual($docmanSf->getSearchType('codex*')    , array('like' => false));
        $this->assertEqual($docmanSf->getSearchType('cod*ex*')   , array('like' => false));
        $this->assertEqual($docmanSf->getSearchType('codex')     , array('like' => false));


    }
}
