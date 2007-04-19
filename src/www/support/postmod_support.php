<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: postmod_support.php 245 2002-06-03 15:35:44Z ljulliar $

	if ($mail_followup) {
		sr_utils_mail_followup($support_id);
	}

?>
