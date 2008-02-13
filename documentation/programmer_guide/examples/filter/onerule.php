<?php
/**
 * This example exposes how UserInputFilter could behave with a system where
 * one rule can check several things at once.
 * @example multiplerule.php Opposite approach:
 */

require_once('common/filter/UserInputFilter.class.php');
require_once('common/include/HTTPRequest.class.php');

$userInputFilter =& new UserInputFilter();

// Variation1:
// Customize a base object on execution
$rule1 =& new UserInputFilter_Integer();
$rule1->expectExist(true);
$rule1->expectMin(0);
$activeGroupIdArray = get_from_somewhere();
$rule1->expectInArray($activeGroupIdArray);
$userInputFilter->addRule('group_id', $rule1);

// Variation2:
// Extend a base object in a custom one that embedd defaults
$rule1 =& new UserInputFilter_ActiveGroupId();
$userInputFilter->addRule('group_id', $rule1);

// Validate
$userInputFilter->validate($request);

// Get the result
$request =& HTTPRequest::instance();
$group_id = $request->get('group_id');
echo $group_id;

?>
