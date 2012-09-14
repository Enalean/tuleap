#!/usr/bin/php -q
<?php

/*
 * Test ID: JBX_14
 * Test description: This test instantiates several JabbeX objects and creates MUC rooms and a shared groups with them.
 * Pre-conditions:
 * 		- Check the pre-conditions for the tests CreateMUC and CreateSharedGroup.
 * Post-conditions:
 * 		- Check the pos-conditions for the tests CreateMUC and CreateSharedGroup.
 * 
 */

require_once("../Jabbex.php");
$jabex1 = new Jabbex("12364789");
$jabex2 = new Jabbex("12365478");
$jabex3 = new Jabbex("12365479");
$jabex4 = new Jabbex("12365489");
$jabex5 = new Jabbex("12365789");

$jabex1->create_shared_group("xingalab","Crossing Alabama");
$jabex2->create_muc_room("xingalab", "My Xingalab Room (Project Name)", "Here I put the description of my room (Projetc short description)", "bbking");
$jabex5->__destruct();
$jabex4->create_shared_group("stubroom","My little stub project");
$jabex3->create_muc_room("stubroom", "My Stub Room (Project Name)", "Here I put the description of my room (Projetc short description)", "bbking");

?>