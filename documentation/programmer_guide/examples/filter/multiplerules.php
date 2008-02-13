<?php
/**
 * This example exposes how UserInputFilter could behave with a system where
 * multiple rules applies on one element.
 * @example onerule.php Opposite approach:
 */

require_once('common/filter/UserInputFilter.class.php');
require_once('common/include/HTTPRequest.class.php');

$userInputFilter =& new UserInputFilter();

// Generic rules (comes with generic error messages)
$userInputFilter->addRule('group_id', 'integer');
$userInputFilter->addRule('group_id', 'mustexist');

// Example of custom error message
$userInputFilter->addRule('group_id', 'positive', 'You should adopt la positive attitude');

// Example of custom rule.
// Check that group_id exists in the DB and is active
$r =& new UserFilter_GroupIdExist();
$userInputFilter->addRule('group_id', $r);

// Validate
$userInputFilter->validate($request);

// Get the result
$request =& HTTPRequest::instance();
$group_id = $request->get('group_id');
echo $group_id;

?>
