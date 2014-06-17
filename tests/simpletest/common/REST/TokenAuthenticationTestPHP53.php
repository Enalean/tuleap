<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
use Tuleap\REST\TokenIsAllowed;

class Rest_TokenAuthenticationTest extends TuleapTestCase {
    
    private $token_is_allowed;
    
    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        $this->token_is_allowed = new TokenIsAllowed();
    }
    
    public function itAllowsOptions() {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $this->assertTrue($this->token_is_allowed->isAllowed());
    }
    
    public function itDoesNotAllowedOtherMethods() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->expectException('DataAccessException');
        $this->token_is_allowed->isAllowed();
    }
}
?>
