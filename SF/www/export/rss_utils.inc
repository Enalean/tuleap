<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

$Language->loadLanguageMsg('export/export');

function rss_setscheme() {
	if ($GLOBALS['scheme'] == "netscape") {
		$GLOBALS['description_maxlen'] = 500;
		$GLOBALS['description_notnull'] = 1;
	}
}

function rss_description ($in) {
  global $Language;
	// if description_maxlen is defined, then truncate appropriately
	// added for netscape rss schema compat
	if (array_key_exists('description_maxlen', $GLOBALS) && $GLOBALS['description_maxlen'] &&
		(strlen($in) > $GLOBALS['description_maxlen'])) {
		$in = substr($in,0,$GLOBALS['description_maxlen']);
	}
	// according to netscape, this cannot be blank
	if ((strlen($in)==0) && $GLOBALS['description_notnull']) {
		$in = $Language->getText('export_rss_utils','no_desc_available');
	}
	return htmlspecialchars($in);
}

?>
