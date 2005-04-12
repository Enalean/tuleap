<?php

rcs_id( '$Id$' );

/**
 Copyright 1999, 2000, 2001, 2002, 2003 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


require_once( 'lib/WikiDB.php' );
require_once( 'lib/WikiDB/backend/file.php' );

/**
 * Wrapper class for the file backend.
 *
 * Authors: Gerrit Riessen, gerrit.riessen@open-source-consultants.de
 *          Jochen Kalmbach <Jochen@Kalmbachnet.de>
 */
class WikiDB_file extends WikiDB
{  
    /**
     * Constructor requires the DB parameters. 
     */
    function WikiDB_file( $dbparams ) 
    {
        $backend = new WikiDB_backend_file( $dbparams );
        $this->WikiDB($backend, $dbparams);
    }
}


// $Log$
// Revision 1.1  2005/04/12 13:33:29  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.4  2003/01/04 03:41:46  wainstead
// Added copyleft flowerboxes
//
// Revision 1.3  2003/01/04 03:29:02  wainstead
// ok, this time log tag for sure.
//
// revision 1.2
// Added credits, php emacs stuff, log tag for CVS.
//
// revision 1.1
// date: 2003/01/04 03:21:00;  author: wainstead;  state: Exp;
// New flat file database for the 1.3 branch thanks to Jochen Kalmbach.


// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   

?>
