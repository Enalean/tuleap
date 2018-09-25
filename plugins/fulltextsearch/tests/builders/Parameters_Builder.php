<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

function aSetOfParameters() {
    return new Parameters_Builder();
}

class Parameters_Builder {
    public $item;
    public $version;
    public $user;
    
    public function __construct() {
        $this->user = mock('PFUser');
    }

    public function withItem($item) {
        $this->item = $item;
        return $this;
    }

    public function withVersion($version) {
        $this->version = $version;
        return $this;
    }
    
    public function build() {
        return array(
                'item'    => $this->item,
                'version' => $this->version,
                'user'    => $this->user
        );
    }
}
?>
