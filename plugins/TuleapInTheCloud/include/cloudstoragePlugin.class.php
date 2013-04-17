<?php
/*
 * Classe CloudStoragePlugin
 */
 
require_once('common/plugin/Plugin.class.php');

class CloudStoragePlugin extends Plugin {
    /**
     *  constructor of CloudStoragePlugin class
     *  @param int id : the plugin id
     *  @return void
     */
    function __construct($id,$debug=IM_DEBUG_OFF) {
        parent::__construct($id);
        
        $this->_addHook('javascript_file', 'jsFile', true);
        $this->_addHook('cssfile', 'cssFile', true);
        $this->_addHook('approve_pending_project', 'projectIsApproved', false);
        $this->_addHook('project_is_suspended_or_pending', 'projectIsSuspendedOrPending', false);
        $this->_addHook('project_is_deleted', 'projectIsDeleted', false);
        $this->_addHook('project_is_active', 'projectIsActive', false);
        $this->_addHook('project_admin_add_user', 'projectAddUser', false);
        $this->_addHook('project_admin_remove_user', 'projectRemoveUser', false);
        $this->_addHook('site_admin_menu_hook',  'siteAdminHooks', true);  
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', true);
        $this->_addHook('site_admin_external_tool_hook', 'site_admin_external_tool_hook', false);
        $this->_addHook('site_admin_external_tool_selection_hook', 'site_admin_external_tool_selection_hook', false);
        $this->_addHook('account_pi_entry', 'im_process_display_user_jabber_id_in_account', false);
        $this->_addHook('user_home_pi_entry', 'im_process_display_user_jabber_id', false);
        $this->_addHook('get_user_display_name', 'im_process_display_presence', false);
        $this->_addHook('widget_instance', 'myPageBox', false);
        $this->_addHook('widgets', 'widgets', false);
        $this->_addHook('user_preferences_appearance', 'user_preferences_appearance', false);
        $this->_addHook('update_user_preferences_appearance', 'update_user_preferences_appearance', false);
        $this->_addHook('project_export_entry', 'provide_exportable_items', false);
        $this->_addHook('get_available_reference_natures', 'getAvailableReferenceNatures', false);
        $this->debug=$debug;
        
    }
	
    /**
     *  method to get the plugin info to be displayed in the plugin administration
     *  @param void
     *  @return void
     */	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'CloudStoragePluginInfo')) {
            require_once('CloudStoragePluginInfo.class.php');
            $this->pluginInfo = new CloudStoragePluginInfo($this);
        }
        return $this->pluginInfo;
    }

	function CallHook($hook, $params) {
		if ($hook == 'hook_name') {
			//do Something
		}
	}
    
    function siteAdminHooks($params) {
       global $Language;
       $link_title = $GLOBALS['Language']->getText('plugin_cloudstorage','service_lbl_key');
       echo '<li><a href="'.$this->getPluginPath().'/">'.$link_title.'</a></li>';
    }
    
    function process() {
    	require_once('CloudStorage.class.php');
		
        $controler =& new CloudStorage($this);
        $controler->process();		
    }
    
    function jsFile($params) {
        // Only include the js files if we're actually in the IM pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="themes/js/jquery.dataTables.min.js"></script>'."\n";
            echo '<script type="text/javascript" src="themes/js/ColReorder.min.js"></script>'."\n";
            echo '<script type="text/javascript" src="themes/js/functions.js"></script>'."\n";
        }
    }    
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the IM plugin pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
        	echo '<link rel="stylesheet" type="text/css" href="themes/css/demo_table.css" />';
            echo '<link rel="stylesheet" type="text/css" href="themes/css/demo_page.css" />';
        }
    }
}
?>
