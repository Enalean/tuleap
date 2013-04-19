<?php
/*
 * Classe CloudStorage.class
 */
 
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');

require_once('CloudStorageViews.class.php');
require_once('CloudStorageActions.class.php');

class CloudStorage extends Controler 
{
    var $plugin;
    
    function CloudStorage(&$plugin) 
    {
        $this->plugin =& $plugin;
    }
    
    function getProperty($name) 
    {
        $info =& $this->plugin->getPluginInfo();
        return $info->getPropertyValueForName($name);
	}
	
	function request() 
	{
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $user = UserManager::instance()->getCurrentUser();
		switch($request->get('action')) 
		{
           	case 'home':
           		$this->view = 'home'; 
           		break;
           				
            case 'dropbox':
            	$this->view = 'dropbox';
            	break;
            	
           	case 'drive':
           		$this->view = 'drive';
           		break;
           		
            default:
            	$this->view = 'home';         
                break;
        }
    }
    
    function getPlugin() 
    {
        return $this->plugin;
    }
}
?>
