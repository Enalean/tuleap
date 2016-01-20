#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_11
 * 
 * Description: Random MUC and shared group related operation are run in batchs of 2 to 8 operations 100 times.
 * 
 * Pre-conditions:
 * - The project id "monster2 must in the Codendi environment (and state = A).
 * - No MUC room named "monster" or "monster2" must exist.
 * 
 * Post-Condition:
 * - The script ends flawlessly and issue no error message.
 */

require_once("../Jabbex.php");

echo "Creating JabbeX object #";

for($i = 0; $i < 100 ; $i++){
	echo " $i";
	$jabbex[$i] = new Jabbex("$i");
}

echo "\n\n Executing... \n #::operation\n";

for($i = 0; $i < 100 ; $i++){
	$operation = rand(0,3);
	
	echo "$i::$operation - ";
	
	switch ($operation){
		case 0:
			$jabbex[$i]->create_shared_group("monster","Monster Stress Test");
			$jabbex[$i]->create_muc_room("monster", "Monster Stress Test", "Stress test for JabbeX", "bbking");
			$jabbex[$i]->delete_muc_room("monster");
			break;
				
		case 1:
			$jabbex[$i]->lock_muc_room("monster2");
			$jabbex[$i]->unlock_muc_room("monster2");
			break;
		case 2:
			$jabbex[$i]->create_muc_room("monster", "Monster Stress Test", "Stress test for JabbeX", "bbking");
			$jabbex[$i]->lock_muc_room("monster");
			$jabbex[$i]->create_muc_room("monster", "Monster Stress Test", "Stress test for JabbeX", "bbking");
			$jabbex[$i]->delete_muc_room("monster");
			break;
		case 3:
			$jabbex[$i]->muc_add_member("monster2", "eclapton");
			$jabbex[$i]->lock_muc_room("monster2");
			$jabbex[$i]->unlock_muc_room("monster2");
			$jabbex[$i]->lock_muc_room("monster2");
			$jabbex[$i]->unlock_muc_room("monster2");
			$jabbex[$i]->muc_remove_member("monster2", "eclapton");
			$jabbex[$i]->lock_muc_room("monster2");
			$jabbex[$i]->unlock_muc_room("monster2");
			break;
	}
}
?>