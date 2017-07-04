<?php

require_once('common/plugin/Plugin.class.php');

class DevtodolistPlugin extends Plugin {

    function __construct($id) {
        parent::__construct($id);
        $this->addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->addHook('cssstyle', 'cssstyle', false);
    }

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'DevtodolistPluginInfo')) {
            require_once('DevtodolistPluginInfo.class.php');
            $this->pluginInfo =& new DevtodolistPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">'.
        $GLOBALS['Language']->getText('plugin_devtodolist', 'descriptor_name')
        .'</a></li>';
    }
    function cssstyle() {
        echo <<<EOS
#form_path {
    font-size:1.5em;
}
#form_path input {
    font-size:1em;
}
#path {
    width:40em;
}
.hint {
    color: #aaa;
}
#autocomp {
  position: absolute;
  width: 500px;
  background-color: white;
  border: 1px solid #888;
  margin: 0px;
  padding: 0px;
}

#autocomp ul {
  list-style-type: none;
  margin: 0px;
  padding: 0px;
  max-height: 20em;
  overflow: auto;
}

#autocomp ul li.selected {
    background-color: #ffb;
}

#autocomp ul li {
  list-style-type:none;
  display: block;
  margin: 0;
  padding: 2px;
  cursor: pointer;
}
EOS;
    }
    public function process() {
        $hp = Codendi_HTMLPurifier::instance();
        $all_path = array(
            '/src/common',
            '/src/www',
            '/plugins',
        );
        $request = HTTPRequest::instance();
        if ($request->isAjax()) {
            echo '<ul>';
            foreach($all_path as $p) {
                if ($request->get('path') && preg_match('`'. preg_quote($request->get('path')) .'`', $p)) {
                    echo '<li>'.  $hp->purify($p, CODENDI_PURIFIER_CONVERT_HTML)  .'</li>';
                }
            }
            echo '</ul>';
            exit;
        }
        $GLOBALS['HTML']->includeJavascriptFile('/scripts/scriptaculous/scriptaculous.js');
        $GLOBALS['HTML']->header(array(
            'title' => $GLOBALS['Language']->getText('plugin_devtodolist', 'descriptor_name'),
            'toptab' => 'admin'
        ));
        $default_path = $request->get('path');
        if ($default_path) {
            $path = array($default_path);
        } else {
            $default_path = '/src/common/tracker/';
            $path = $all_path;
        }
        echo '<form action="" method="GET" id="form_path">
            <label for="path">Path: </label><input type="text" name="path" value="'. $default_path .'" id="path" />
            <div id="autocomp"></div>
            <input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />
            </form>
        <script type="text/javascript">
        new Ajax.Autocompleter("path", "autocomp", "?", {frequency:0.2});
        </script>
        <hr size="1" />
        ';

        foreach($path as $p) {
            echo '<h2>'.  $hp->purify($p, CODENDI_PURIFIER_CONVERT_HTML)  .'</h2>';
            flush();
            echo '<pre>';
            $cmd = 'cd '. $GLOBALS['codendi_dir']. $p .' ; grep -rinE "TODO" * |
                        grep -v ".svn" |
                        grep -v ".php~" |
                        grep -v ".class~" |
                        grep -v ".html~" |
                        grep -v ".zargo" |
                        grep -v ".tab~"';
            passthru($cmd);
            echo '</pre>';
        }
        $GLOBALS['HTML']->footer(array());
    }
}

?>
