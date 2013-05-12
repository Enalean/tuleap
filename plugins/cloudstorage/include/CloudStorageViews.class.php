<?php
/*
 * Classe CloudStoragePluginInfo
 */
 
require_once('pre.php');
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('www/project/export/project_export_utils.php');

require_once('CloudStorageDao.class.php');

class CloudStorageViews extends Views {
	
    function CloudStorageViews(&$controler, $view=null) {
        $this->View($controler, $view);
    }
    
    function display($view='') {
        parent::display($view);
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
        	$GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $group_id,'toptab' => 'cloudstorage', 'selected_top_tab' => 'cloudstorage'));
        	if (user_ismember($request->get('group_id'))) 
        	{
            	echo '<b><a href="/plugins/cloudstorage/?group_id='. $request->get('group_id') .'&amp;action=home">'. $GLOBALS['Language']->getText('plugin_cloudstorage', 'home') . '</a> | </b>';
            	echo '<b><a href="/plugins/cloudstorage/?group_id='. $request->get('group_id') .'&amp;action=dropbox">'. $GLOBALS['Language']->getText('plugin_cloudstorage', 'dropbox') . '</a> | </b>';
            	echo '<b><a href="/plugins/cloudstorage/?group_id='. $request->get('group_id') .'&amp;action=drive">'. $GLOBALS['Language']->getText('plugin_cloudstorage', 'drive') . '</a></b>';
        	}
        	
            //echo $this->_getHelp();
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
    function home()
    {
    	$request = HTTPRequest::instance(); 
    	
    	$cloudstorage_dao = new CloudstorageDao(CodendiDataAccess::instance());
    	$res_dropbox_defcsid = $cloudstorage_dao->select_default_cloudstorage_id('dropbox');
    	$res_drive_defcsid = $cloudstorage_dao->select_default_cloudstorage_id('drive');
    	
    	echo '<h2>' . $GLOBALS['Language']->getText('plugin_cloudstorage', 'home_title') . '</h2>';
    	
    	/*echo'
    		<div class="content" style="text-align:center;">
				<a href="/plugins/cloudstorage/?group_id='. $request->get('group_id') .'&amp;action=dropbox"><img src="./themes/img/dropbox.png" alt="Dropbox" /></a>
				<a href="/plugins/cloudstorage/?group_id='. $request->get('group_id') .'&amp;action=drive"><img src="./themes/img/google-drive.jpg" alt="Google drive" /></a>
			</div>'
		;*/
		
		echo '
			<script language="javascript">			
				function affichage_popup(nom_de_la_page, nom_interne_de_la_fenetre) {
					window.open(nom_de_la_page, nom_interne_de_la_fenetre, config="height=480, width=640, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no")
				}
			</script>	
					
			<form class="cloudstorage_form" name="input" action="/plugins/cloudstorage/?group_id=1&action=home" method="post">
				<div class="cloudstorage_new_item">
					<div>
						<h3 style="border-bottom: 1px solid #999999;">Settings</h3>
						<table>
							<tbody>
								<tr>
									<td><span title="Dropbox Folder ID:">Default Dropbox Folder ID:</span></td>
									<td>
										<input type="text" id="default_dropbox_id" size="30" value="'.$res_dropbox_defcsid.'" name="default_dropbox_id" class="text_field" onclick="javascript:affichage_popup(\'https://'.$_SERVER['HTTP_HOST'].'/plugins/cloudstorage/?group_id=1&action=dropbox&docman=yes&default=yes\', \'Select folder name from your Dropbox storage\');">
										<img src="themes/img/delete.gif" alt="Delete" onclick="javascript:document.getElementById(\'default_dropbox_id\').value = \'\';" />
									</td>
								</tr>
								<tr>
									<td><span title="Drive Folder ID:">Default Google Drive Folder ID:</span></td>
									<td>
										<input type="text" id="default_drive_id" size="30" value="'.$res_drive_defcsid.'" name="default_drive_id" class="text_field" onclick="javascript:affichage_popup(\'https://'.$_SERVER['HTTP_HOST'].'/plugins/cloudstorage/?group_id=1&action=drive&docman=yes&default=yes\', \'Select folder id from your Drive storage\');">
										<img src="themes/img/delete.gif" alt="Delete" onclick="javascript:document.getElementById(\'default_drive_id\').value = \'\';" />
									</td>
								</tr>
							</tbody>
						</table>				
					</div>
					<br />
					<input type="hidden" name="action" value="update_default_cloudstorage_id">
					<input type="submit" value="Update settings">
				</div>
			</form> 		
		';
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
