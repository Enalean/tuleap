<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

function bookmark_add ($bookmark_url, $bookmark_title="") {
	if (!$bookmark_title) {
		$bookmark_title = $bookmark_url;
	}
	$result = db_query("INSERT into user_bookmarks (user_id, bookmark_url, "
		. "bookmark_title) values ('".user_getid()."', '$bookmark_url', "
		. "'$bookmark_title');");
	if (!$result) {
		echo db_error();
	}
}

function bookmark_edit ($bookmark_id, $bookmark_url, $bookmark_title) {
	db_query("UPDATE user_bookmarks SET bookmark_url='$bookmark_url', "
		."bookmark_title='$bookmark_title' where bookmark_id='$bookmark_id' AND user_id='". user_getid() ."'");
}

function bookmark_delete ($bookmark_id) {
	db_query("DELETE from user_bookmarks WHERE bookmark_id='$bookmark_id' "
		. "and user_id='". user_getid() ."'");
}

?>
