<?php
class CheckParameters {

	/*
	 * Check whether the variables passed as parameters are valid non-empty strings.
	 */
	function NotEmptyString(){
		$numargs = func_num_args();
		if ($numargs<1) return false;

		$arg_list = func_get_args();
		foreach($arg_list as $arg){
			$arg = trim($arg);
			if(!( isset($arg) && is_string($arg) && strlen($arg)>0 )){
				return false;
			}
		}

		return true;
	}
}
?>