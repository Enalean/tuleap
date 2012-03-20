<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class testsPluginRequest {
    
    protected $cover_code   = false;
    protected $show_pass    = false;
    protected $order        = 'normal';
    protected $order_values = array('normal', 'random', 'invert');
    protected $tests_to_run = array();
    
    public function parse($request) {
        foreach($request as $property=> $value) {
            $setProperty = 'set'.ucfirst(preg_replace_callback('@[_](.)@', array($this, 'replaceUnderscore'), $property));
            if (method_exists($this, $setProperty)) {
                $this->$setProperty($value);
            }
        }
    }
    
    protected function replaceUnderscore($match) {
        return ucfirst($match[1]);
    }
    
    public function setCoverCode($cover_code) {
        $this->cover_code = (bool) $cover_code;
    }
    
    public function setShowPass($show_pass) {
        $this->show_pass = (bool) $show_pass;
    }
    
    public function setOrder($order) {
        $order = strtolower($order);
        if (in_array($order, $this->order_values)) {
            $this->order = $order;
        }
    }
    
    public function setDisplay($display) {
        $this->display = $display;
    }
    
    public function setTestsToRun( array $tests_to_run) {
        
        $this->tests_to_run = $this->parseTestsToRun($tests_to_run);
    }
    
    private function parseTestsToRun($tests_to_run) {
        $to_run = array();
        foreach ($tests_to_run as $key=>$value) {
            if (is_array($value)) {
                $to_run[$key] = $this->parseTestsToRun($value);
            } elseif ($value == 1 && $key !== '_do_all') {
                $to_run[] = $key; 
            }
        }
        return $to_run;
    }
    
    
    public function getCoverCode() {
        return $this->cover_code;
    }
    
    public function getShowPass() {
        return $this->show_pass;
    }
    
    public function getOrder() {
        return $this->order;
    }
    
    public function getTestsToRun() {
        return $this->tests_to_run;
    }
    
    public function getDisplay() {
        return $this->display;
    }
    
}
?>