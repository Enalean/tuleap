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

class TestsPluginRunnerPresenter {
    public $show_pass_checked='';
    public $cover_code_checked='';
    public $order_normal_checked='';
    public $order_random_checked='';
    public $order_invert_checked='';
    protected $results;
    protected $navigator;
    public function __construct($request, $navigator, $results) {
        $this->request   = $request;
        $this->navigator = $navigator;
        $this->results   = $results;
    }
    
    public function show_pass_checked() {
        return $this->checked($this->request->getShowPass());
    }
    
    public function cover_code_checked() {
        return $this->checked($this->request->getCoverCode());
    }
    
    public function order_random_checked() {
        return $this->checked($this->request->getOrder() == 'random');
    }
    
    public function order_normal_checked() {
        return $this->checked($this->request->getOrder() == 'normal');
    }

    public function order_invert_checked() {
        return $this->checked($this->request->getOrder() == 'invert'); 
    }
    
    protected function checked($bool) {
        if ($bool == true ) {
            return ' checked="checked"';
        } else {
            return '';
        }
    }
    
    
    public function navigator() {
        return $this->navigator;
    }
    
    public function results() {
        return $this->results;
    }
    
}
?>