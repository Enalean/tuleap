<?php
/**
 * Copyright Enalean (c) 2017-2019. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 */
/**
 * Simple API script available through HTTP
 *
 * input parameters:
 *    group_id : project where references are defined
 *    text     : input text
 *    rev_id : number of the revision
 *    art_id : id of the target artifact
 * output: references extracted from the input text
 * output format:
reference description
match
link
<newline>
reference description
...
 * example:
Tracker Artifact
art #6840
http://codendi.example.com/goto?key=art&val=6840&group_id=109

Tracker Artifact
art #6841
http://codendi.example.com/goto?key=art&val=6841&group_id=109

*/

require_once __DIR__ . '/../../include/pre.php';

header('Content-type: text/plain');

$request = HTTPRequest::instance();

$group_id = 100;
if ($request->existAndNonEmpty('group_id')) {
    $group_id = $request->getValidated('group_id', 'uint', 100);
} else {
    $group_name = $request->getValidated('group_name', 'string', false);
    if ($group_name != false) {
        $project  = ProjectManager::instance()->getProjectByUnixName(trim($group_name));
        if ($project) {
            $group_id = $project->getID();
        }
    }
}

if (!$request->get('text') || !$request->get('login') || !$request->get('type') || !$request->get('rev_id')) {
    echo $GLOBALS['Language']->getText('include_exit', 'missing_param_err') . "\n";
    echo $GLOBALS['Language']->getText('project_reference', 'extract_syntax');
    exit;
}

$user_id = 100;
$login = $request->getValidated('login', 'string', 'None');
$user = UserManager::instance()->getUserByUserName(trim($login));
if ($user !== null) {
    $user_id = $user->getId();
}

$text = trim($request->get('text'));
$source_id = trim($request->get('rev_id'));
$source_type = trim($request->get('type'));

$reference_manager = ReferenceManager::instance();
$reference_manager->extractCrossRef($text, $source_id, $source_type, $group_id, $user_id);

$refs = $reference_manager->extractReferences($text, $group_id);
if (isset($refs)) {
    foreach ($refs as $ref_instance) {
        $ref = $ref_instance->getReference();
        print $ref->getDescription() . "\n";
        print $ref_instance->getMatch() . "\n";
        print $ref_instance->getFullGotoLink() . "\n\n";
    }
}
exit;
