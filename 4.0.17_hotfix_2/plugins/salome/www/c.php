<?php

$matches = array();
preg_match_all('`/plugins/salome/c/(\d+)/(\w+)/(.+)`', $_SERVER['REQUEST_URI'], $matches);

$group_id = $matches[1][0];
$base     = $matches[2][0];
$file     = $matches[3][0];


if (in_array($file, array('cfg/DB_Connexion.properties', 'plugins/CfgPlugins.properties'))) {
    //Each project has its own configuration
    require_once('pre.php');
    require_once('common/plugin/PluginManager.class.php');
    $plugin_manager =& PluginManager::instance();
    $salome =& $plugin_manager->getPluginByName('salome');
    if ($salome && $plugin_manager->isPluginAvailable($salome)) {
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        require_once('common/include/Properties.class.php');
        $config_file = dirname(__FILE__) .'/'. $base .'/'. $file;
        $p = new Properties();
        $p->load(file_get_contents($config_file));
        switch ($file) {
        case 'cfg/DB_Connexion.properties':
            require_once('../include/SalomeTMFURLManager.class.php');
            $p->setProperty('SQLEngineSOAP', ($user->getPreference('plugin_salome_use_soap_'. $group_id) ? 'org.objectweb.salome_tmf.codendi.soap.SQLObjectFactory_SOAP_Codendi' : 'false'));
            $p->setProperty('WithICAL', ($salome->getConfigurationOption($group_id, 'WithICAL') ? 'true' : 'false'));
            $p->setProperty('LockOnTestExec', ($salome->getConfigurationOption($group_id, 'LockOnTestExec') ? 'true' : 'false'));
            $p->setProperty('LockExecutedTest', ($salome->getConfigurationOption($group_id, 'LockExecutedTest') ? 'true' : 'false'));
            if (session_issecure()) {
                $url = $GLOBALS['sys_https_host'];
            } else {
                $url = $GLOBALS['sys_default_domain'];
    	    }
            $url_manager = new SalomeTMFURLManager($url);
            $jdbc_url = $url_manager->getJDBCUrl();
            $p->setProperty('URL', $jdbc_url);
            $p->setProperty('User', 'salomeadm');
            break;
        case 'plugins/CfgPlugins.properties':
            $p->setProperty('pluginsList', implode(', ', $salome->getPluginsList($group_id)));
            break;
        }
        header('Content-type: text/plain');
        echo $p->toString();
    }
} else if (preg_match('`plugins/[^/]*/[^/]*.jnlp$`', $file)) {
    //Define the codebase foreach plugin jnlp
    require_once('pre.php');
    $jnlp = simplexml_load_file(dirname(__FILE__) .'/'. $base .'/'. $file);
    $jnlp['codebase'] = get_server_url() . '/plugins/salome/c/'. $group_id .'/'. $base .'/'. dirname($file);
    header('Content-type: text/xml');
    echo $jnlp->asXML();
} else {
    header('Location: /plugins/salome/'. $base .'/'. $file);
    exit;
}
?>
