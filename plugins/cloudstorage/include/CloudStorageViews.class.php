<?php
/*
 * Classe CloudStoragePluginInfo
 */
 
require_once('pre.php');
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('www/project/export/project_export_utils.php');

//require_once('IMDao.class.php');
//require_once('IMDataAccess.class.php');
//require_once('JabbexFactory.class.php');

//require_once('IMMucLogManager.class.php');


//ini_set('display_errors', 1);
//ini_set('log_errors', 1);
//error_reporting(E_ALL);

class CloudStorageViews extends Views {
	
    function CloudStorageViews(&$controler, $view=null) {
        $this->View($controler, $view);
    }
    
    function display($view='') {
        //if ($view == 'dropbox') {
            //$this->$view();
        //} else {
            parent::display($view);
        //}
    }
    
    function header() {
        $request = HTTPRequest::instance();
        
        $group_id = $request->get('group_id');

        if ($this->getControler()->view == 'codendi_cloudstorage_admin') 
        {
            $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'selected_top_tab' => 'admin'));
        } 
        else 
        {
            $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $group_id,'toptab' => 'CloudStorage', 'selected_top_tab' => 'CloudStorage'));
        	
        	if (user_ismember($request->get('group_id'))) 
        	{
            	echo '<b><a href="/plugins/cloudstorage/?group_id='. $request->get('group_id') .'&amp;action=home">'. $GLOBALS['Language']->getText('plugin_cloudstorage', 'home') . '</a> | </b>';
            	echo '<b><a href="/plugins/cloudstorage/?group_id='. $request->get('group_id') .'&amp;action=dropbox">'. $GLOBALS['Language']->getText('plugin_cloudstorage', 'dropbox') . '</a> | </b>';
            	echo '<b><a href="/plugins/cloudstorage/?group_id='. $request->get('group_id') .'&amp;action=drive">'. $GLOBALS['Language']->getText('plugin_cloudstorage', 'drive') . '</a> | </b>';
        	}
        	
            echo $this->_getHelp();
        }
    }
    
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }   
    
    function _getHelp($section = '') {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        return '<b><a href="javascript:help_window(\''.get_server_url().'/documentation/user_guide/html/'.UserManager::instance()->getCurrentUser()->getLocale().'/CloudStoragePlugin.html'.$section.'\');">'.$GLOBALS['Language']->getText('global', 'help').'</a></b>';
    }
    
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_cloudstorage','title');
    }
    
    // {{{ Views
    function codendi_cloudstorage_admin() {
		echo '<h2><b>'.$GLOBALS['Language']->getText('plugin_cloudstorage','title').'</b></h2>';
		echo '<h3><b>'.$GLOBALS['Language']->getText('plugin_cloudstorage','view').'</b></h3>';
		
	}
	
    function home()
    {
    	$request = HTTPRequest::instance(); 
    	
    	echo '<h2>' . $GLOBALS['Language']->getText('plugin_cloudstorage', 'home_title') . '</h2>';
    	
    	require('../www/overview.php');
    }
    
    function dropbox()
    {
    	$request = HTTPRequest::instance();
    	
    	echo '<h2>' . $GLOBALS['Language']->getText('plugin_cloudstorage', 'dropbox_title') . '</h2>';
    	
    	require('../www/dropbox/metadatas.php');
    }
    
    function drive()
    {
    	$request = HTTPRequest::instance();
    	
    	echo '<h2>' . $GLOBALS['Language']->getText('plugin_cloudstorage', 'drive_title') . '</h2>';
    	
    	include('../www/drive.php');
    	
    }  
    // }}}
    

}

?>
