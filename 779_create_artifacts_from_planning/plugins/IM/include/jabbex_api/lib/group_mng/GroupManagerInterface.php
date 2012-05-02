<?php
interface GroupManagerInterface{
	
	/*
	 * Uses the information of the global variables of the class to
	 * make a shared group available by setting its parameters.
	 */
	function create_shared_group();
	
	function set_group_short_name($short_name);
	function set_group_full_name($full_name);
	function get_group_short_name();
	function get_group_full_name();
	public function get_log();	
	
	function set_jab_connector(&$jab);
		
}
?>