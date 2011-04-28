<?php

require_once('common/filter/UserInputFilter.class.php');
require_once('common/include/HTTPRequest.class.php');

$userInputFilter =& new UserInputFilter();
$rule1 =& new UIF_Integer();
$userInputFilter->addRule('group_id', $rule1);
$userInputFilter->validate($request);

$request =& HTTPRequest::instance();
$group_id = $request->get('group_id');
echo $group_id;

?>
