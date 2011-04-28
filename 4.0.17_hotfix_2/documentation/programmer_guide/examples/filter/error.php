<?php

require_once('common/filter/UserInputFilter.class.php');
require_once('common/include/HTTPRequest.class.php');

$userInputFilter =& new UserInputFilter();
$rule1 =& new UIF_Integer();
$userInputFilter->addRule('group_id', $rule1);
if($userInputFilter->validate($request)) {
    $request =& HTTPRequest::instance();
    $group_id = $request->get('group_id');
    echo $group_id;
} else {
    // Solution #1
    // Get all errors
    $errorIterator =& $userInputFilter->getErrors();
    $errorIterator->rewind();
    while($errorIterator->valid()) {
        $error =& $errorIterator->current();
        echo $error['argName']." failed with ".$error['rule']->getComment();
        $errorIterator->next();
    }

    // Solution #2
    // Test one element
    if($userInputFilter->isError('group_id')) {
        trigger_error('Invalid group_id', E_USER_ERROR);
    }

    // Solution #3
    // Get one element
    if($userInputFilter->getError('group_id')) {
        $error =& $userInputFilter->getError('group_id');
        echo "group_id failed with ".$error->getComment();
    }

}
?>
