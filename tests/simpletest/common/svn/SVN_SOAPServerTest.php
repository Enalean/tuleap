<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/svn/SVN_SOAPServer.class.php';

class SVN_SOAPServerTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $GLOBALS['svn_prefix'] = '/data/svnroot';
    }
    
    public function tearDown() {
        parent::tearDown();
        unset($GLOBALS['svn_prefix']);
    }
    
    public function itShowsOnlyTheDirectoryContents() {
        $content = '';
        $content .= "/my/Project/tags\n";
        $content .= "/my/Project/tags/1.0\n";
        $content .= "/my/Project/tags/2.0\n";
        
        $svn_soap = TestHelper::getPartialMock('SVN_SOAPServer', array('getDirectoryContent'));
        stub($svn_soap)->getDirectoryContent('/data/svnroot/gpig', '/my/Project/tags')->returns($content);
    
        $tags = $svn_soap->getSVNPaths('gpig', '/my/Project/tags');
        $this->assertEqual($tags, array('1.0', '2.0'));
    }
}

?>
