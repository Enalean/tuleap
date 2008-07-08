<?php
require_once('pre.php');
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once(dirname(__FILE__)."/install/IMDataIntall.class.php");

class IMViews extends Views{
	var $request;
	var $install;
    function IMViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $GLOBALS['Language']->loadLanguageMsg('IM', 'IM');//im=intant messaging
        $this->request =& HTTPRequest::instance();
        $this->install=new IMDataIntall();
    }
    
    function header() {
        $request =$this->request;
        $group_id=$request->get('group_id');
	        $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $request->get('group_id'), 'toptab' => 'IM','selected_top_tab' => 'admin'));
	       	$title = $GLOBALS['Language']->getText('plugin_im_admin','im_admin_title');
	       	echo '<h2><b>'.$title.'</b></h2>';
    }
    
    function _getHelp($section = '') {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        return '<b><a href="javascript:help_window(\''.get_server_url().'/plugins/IM/documentation/'.user_get_languagecode().'/'.$section.'\');">'.$GLOBALS['Language']->getText('global', 'help').'</a></b>';
    }
    
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function codex_im_admin () {
		$this->install->admin_install_muc_and_grp();
	}
    // }}}
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_im','title');
    }
    
    
    
    /**
     * view for showing the roster's member for the shared groups corresponding th currente project
     */
       function im_roster_member() {
       // echo file_get_contents($GLOBALS['Language']->getContent('intro', null, 'IM'));//intro-->documentation
      //echo '<br> appel de la fonction im_roster_member() de la classe IMViews.class.php<br>';
       $project= new Group($this->request->get("group_id"));
       $project_unix_name=$project->getUnixName();
       $project_public_name=$project->getPublicName();
       echo "<font color=\"red\">NOM DU GROUPE PARTAGE  : </font>".$project_unix_name."  <br><font color=\"red\">Son nom public :</font> ".$project_public_name;
       $html=new Layout(null);
       $members_id_array=$project->getMembersId();
       $members_unix_name_array=array();
       $members_real_name_array=array();
       $i=0;
       $members_object_array=array();
       $tab_title=$GLOBALS['Language']->getText('plugin_im','im_roster_member');
       echo $html->box1_top($tab_title);
       //building the members tables box and initialised "$members_unix_name_array" ,"$members_real_name_array" , "$members_object_array"
       while (isset($members_id_array[$i])&&$members_id_array[$i]) {
			$members_object_array[$i]=new User($members_id_array[$i]);
			$members_unix_name_array[$i]=$members_object_array[$i]->getName();
			$members_real_name_array[$i]=$members_object_array[$i]->getRealName();
			echo $html->box1_middle($members_unix_name_array[$i]." ( ".$members_real_name_array[$i]." )");
			$i++;
		}
       
       echo $html->box1_bottom();
       
    }

}

?>