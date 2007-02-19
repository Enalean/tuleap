<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: index.php 1405 2005-03-21 14:41:41Z guerin $

require_once('pre.php');
    
$Language->loadLanguageMsg('file/file');

if (! isset($group_id) || ! $group_id) {
    exit_error($Language->getText('file_file_utils', 'g_id_err'),$Language->getText('file_file_utils', 'g_id_err'));
}

header ("Location: /file/admin/editpackages.php?group_id=". $group_id);

?>
