<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

$expl_pathinfo = explode('/',$PATH_INFO);

Header ('Location: /projects/'.$expl_pathinfo[1].'/');

?>
