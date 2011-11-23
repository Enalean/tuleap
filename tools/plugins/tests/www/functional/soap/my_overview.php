<?php


///////////////////////////////////////
// Configuration part
$test_server = 'http://' .$_SERVER['SERVER_ADDR'] /*.':'. $_SERVER['SERVER_PORT']*/;

$login = 'sandrae ';
$password = 'sandrae';
///////////////////////////////////////

try {
    
    $client = new SoapClient($test_server.'/soap/codendi.wsdl.php?wsdl', 
                                array(//'trace' => true,
                                      'trace'      => 1,
                                      'exceptions' => 0,
                                      'soap_version' => SOAP_1_1,
                                      //'proxy_host' => 'localhost', 
                                      //'proxy_port' => 8008
                                ));
    
    $session =  $client->login($login, $password);
    
    $session_hash = $session->session_hash;
    $my_projects = $client->getMyProjects($session_hash);
    
    echo '<ul>';
    foreach($my_projects as $project) {
        echo '<li>'.$project->group_name.'</li>';
        
        if ($project->group_id != 1) {
            $trackers = $client->getTrackerList($session_hash, $project->group_id);

            echo '<ul>';
            echo '<li>TRACKER SERVICE</li>';
            // TRACKERS
            echo '<ul>';
            foreach ($trackers as $tracker) {
                echo '<li>'.$tracker->name.'</li>';
            }
            echo '</ul>';
            
            // FILE RELEASE SYSTEM
            echo '<li>FRS SERVICE</li>';
            $packages = $client->getPackages($session_hash, $project->group_id);
            echo '<ul>';
            foreach ($packages as $package) {
                $releases = $client->getReleases($session_hash, $project->group_id, $package->package_id);
                echo '<li>';
                echo $package->name;
                echo '<ul>';
                foreach ($releases as $release) {
                    $files = $client->getFiles($session_hash, $project->group_id, $package->package_id, $release->release_id);
                    echo '<li>';
                    echo $release->name;
                    echo '<ul>';
                    foreach($files as $file) {
                        echo '<li>'.$file->file_name.' ('.$file->file_size.' Ko)</li>';
                    }
                    echo '</ul>';
                    echo '</li>';
                }
                echo '</ul>';
                echo '</li>';
            }
            echo '</ul>';
            
            // DOCMAN
            echo '<li>DOCMAN SERVICE (one level)</li>';
            $root_item_id = $client->getRootFolder($session_hash, $project->group_id);
            $folder_list = $client->listFolder($session_hash, $project->group_id, $root_item_id);
            echo '<ul>';
            foreach ($folder_list as $folder) {
                echo '<li>';
                echo $folder->title;
                echo '</li>';
            }
            echo '</ul>';
        }
    }
    echo '</ul>';
    
} catch (SoapFault $fault) {
    var_dump($fault);
}

?>
