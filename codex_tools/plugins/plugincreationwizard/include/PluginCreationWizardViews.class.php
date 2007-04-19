<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * PluginCreationWizardViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('Template.class.php');

class PluginCreationWizardViews extends Views {
    
    function PluginCreationWizardViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $GLOBALS['Language']->loadLanguageMsg('plugincreationwizard', 'plugincreationwizard');
    }
    
    function header() {
        $title = $GLOBALS['Language']->getText('plugin_plugincreationwizard','title');
        $GLOBALS['HTML']->header(array('title'=>$title));
        echo '<h2>'.$title.'</h2>';
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function introduction() {
        $content =& new Template('tpl/00_introduction.tpl');
        echo $this->_main(0, $content);
    }
    
    function descriptor() {
        $content =& new Template('tpl/01_descriptor.tpl');
        echo $this->_main(1, $content);
    }
    
    function webspace() {
        $content =& new Template('tpl/02_webspace.tpl');
        echo $this->_main(2, $content);
    }
    
    function hooks() {
        $content =& new Template('tpl/03_hooks.tpl');
        echo $this->_main(3, $content);
    }
    
    function database() {
        $content =& new Template('tpl/04_database.tpl');
        echo $this->_main(4, $content);
    }
    
    function finish() {
        $content =& new Template('tpl/05_finish.tpl');
        echo $this->_main(5, $content);
    }
    function end() {
        echo 'Your plugin should be created. See you soon!';
    }
    // }}}
    function _main($current, $content) {
        $views    = array('introduction', 'descriptor', 'webspace',       'hooks', 'database', 'finish');
        $sections = array('Introduction', 'Descriptor', 'Web&nbsp;Space', 'Hooks', 'Database', 'Finish');

        $main =& new Template('tpl/main.tpl');
        {
            //{{{ Top
            $top =& new Template('tpl/top.tpl');
            {
                $tds      = array();
                $percent  = floor(100/count($sections));
                foreach($sections as $numero => $section) {
                    $td =& new Template('tpl/top_tds.tpl');
                    $td->set('percent', $percent);
                    $td->set('numero', $numero);
                    $td->set('section', $section);
                    if ($numero < $current) {
                        $td->set('class', 'previous');
                        $td->set('start_link', '<a href="?goto='.$views[$numero].'">');
                        $td->set('end_link', '</a>');
                    } elseif ($numero == $current) {
                        $td->set('class', 'current');
                        $td->set('start_link', '');
                        $td->set('end_link', '');
                    } else {
                        $td->set('class', 'next');
                        $td->set('start_link', '');
                        $td->set('end_link', '');
                    }
                    $tds[] = $td->fetch();
                }
                $top->set('tops', $tds);
            }
            $main->set('top', $top->fetch());
            //}}}
            
            //{{{ Content
            $main->set('content', $content);
            //}}}
            
            //{{{ footer
            $footer =& new Template('tpl/footer.tpl');
            {
                $footer->set('back_is_disabled',   ($current == 0)?'disabled="disabled"':'');
                $footer->set('next_is_disabled',   ($current == (count($views)-1))?'disabled="disabled"':'');
                $footer->set('finish_is_disabled', ($current != (count($views)-1))?'disabled="disabled"':'');
            }
            $main->set('footer', $footer->fetch());
            //}}}
        }
        return $main->fetch();
    }
}


?>
