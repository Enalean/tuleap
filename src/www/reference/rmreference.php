<?php

require_once('pre.php');
require_once('common/reference/ReferenceManager.class.php');

$target_id = $request->get('target_id');
$target_gid = $request->get('target_gid');
$target_type = $request->get('target_type');
$target_key = $request->get('target_key');
    
$source_id = $request->get('source_id');
$source_gid = $request->get('source_gid');
$source_type = $request->get('source_type');
 $source_key = $request->get('source_key');
    
$user = UserManager::instance()->getCurrentUser();

$project_admin = $user->isMember($target_gid, 'A') ;
if(!$project_admin){
    $project_admin_source = $user->isMember($source_gid, 'A') ;
    if ($project_admin_source){
           $project_admin = true;
    }
}
      
if($project_admin){
    $cross_reference = new CrossReference(
        $source_id,
        $source_gid,
        $source_type,
        $source_key,
        $target_id,
        $target_gid,
        $target_type,
        $target_key,
        $user->getId()
    );
    $reference_manager = new ReferenceManager();
    $reference_manager->removeCrossReference($cross_reference);
}

?>
