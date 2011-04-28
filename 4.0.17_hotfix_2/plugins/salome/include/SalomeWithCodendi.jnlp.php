<?php
require_once('common/user/UserManager.class.php');
require_once('SalomeTMFPluginsManager.class.php'); 

$um =& UserManager::instance();
$user =& $um->getCurrentUser();

if ($user->isAnonymous()) {
   exit_not_logged_in();
}

// ==========================================================================
$request =& HTTPRequest::instance();
$pm = ProjectManager::instance();
$project = $pm->getProject($request->get('group_id'));
// ==========================================================================

// ==========================================================================
// Salome Arguments:
// ==========================================================================
$client_mode = $user->getPreference('plugin_salome_use_soap_'. $project->getId()) ? 's' : 'j';
$slm_codebase = get_server_url() . '/plugins/salome/c/'. $project->getId() .'/'. $client_mode;

$slm_project_name = $project->getUnixName();
$slm_user_name = $user->getName();
$slm_locale =  $user->getLocale();

// ==========================================================================
// Codendi Arguments:
// ==========================================================================
$cx_jri_svr_url = get_server_url();
$cx_svr_url = $cx_jri_svr_url;  // for now: codendi and jri servers are the same
$cx_session_hash = session_hash();
$cx_user_pass = $user->getUserPw();

$spm =& SalomeTMFProxyManager::instance();
$salome_proxy = $spm->getSalomeProxyFromCodendiUserID(user_getid());
if ($salome_proxy && $salome_proxy->isActive()) {
	$cx_proxy_url = $salome_proxy->getProxy();
	$cx_proxy_user = $salome_proxy->getProxyUser();
	$cx_proxy_pass = $salome_proxy->getProxyPassword();
} else {
	$cx_proxy_url = '';
	$cx_proxy_user = '';
	$cx_proxy_pass = '';
}

// ==========================================================================

header('Content-type: application/x-java-jnlp-file');
//header('Content-type: text/xml'); //For debug

// ==========================================================================
echo '<';
?>?xml version="1.0" encoding="utf-8"?>
<!-- JNLP File for Salome V3 -->
<!-- Edit information YOUR_HOST and SALOME_DIR -->
<!-- Uncomment plugin entrie (extension) when installed) -->
<!--
  Salome (JDBC or SOAP) With Codendi
-->
<jnlp codebase="<?=$slm_codebase?>"
version="3" >
<information>
    <title>Salome TMF (3)</title>
    <vendor>Mikael Marche, Faycal Sougrati (FTRD MAPS/AMS) </vendor>
    <homepage href="https://wiki.objectweb.org/salome-tmf/"/>
    <description>Salome TMF (3 )</description>
    <description kind="short">Salome TMF</description>
</information>

<security>
    <all-permissions/>
</security>

<resources>
    <j2se version="1.5+"/>

    <!-- Main Salome -->
    <!-- Codendi Integration -->
    <jar href="salome_tmf_codex.jar" main="true"/>
    <!-- -->
    <jar href="salome_tmf_ihm.jar" main="false"/>
    <jar href="salome_tmf_coreplug.jar" main="false"/>
    <jar href="salome_tmf_data.jar" main="false"/>
    <jar href="salome_tmf_api.jar" main="false"/>
    <jar href="salome_tmf_login.jar" main="false"/>

    <!-- Common Salome -->
    <jar href="commons-logging.jar" main="false"/>
    <jar href="jcommon-1.0.0.jar" main="false"/>
    <jar href="jpf-0.3.jar" main="false"/>
    <jar href="dom4j-1.5.jar" main="false"/>
    <jar href="jfreechart-1.0.1.jar" main="false"/>
    <jar href="log4j-1.2.6.jar" main="false"/>
    <jar href="driver.jar" main="false"/>
    <jar href="jnlp.jar" main="false"/>
    
    <!-- Always Necessary (JDBC or SOAP) because of CodendiTraker -->
    <jar href="axis.jar" download="lazy"/>
    <jar href="jaxrpc.jar" download="lazy"/>
    <!-- -->
    <jar href="activation.jar" download="lazy"/>
    <jar href="commons-discovery-0.2.jar" download="lazy"/>
    <jar href="saaj.jar" download="lazy"/>
    <jar href="wsdl4j-1.5.1.jar" download="lazy"/>

    <?php
    if ($client_mode == 'j') {
        echo '
    <!-- JDBC Salome -->
    <jar href="salome_tmf_sqlengine.jar" main="false"/>
        ';
    } else {
        echo '
    <!-- SOAP Salome -->
    <jar href="salome_tmf_soap.jar" download="lazy"/>
    <jar href="axis-ant.jar" download="lazy"/>
    <jar href="axis-schema.jar" download="lazy"/>
    <jar href="mail.jar" download="lazy"/>
        ';
    }
    ?>

    <!-- Plugins Extension -->
    <?php
        $salome_plugins_manager = new SalomeTMFPluginsManager($this->getControler());
        $salome_plugins = $salome_plugins_manager->getActivatedPlugins($project->getId());
        foreach ($salome_plugins as $plugin_name) {
            if ($plugin_name != 'core' && $plugin_name != 'gen_doc_xml') {
                echo '
                <extension name="'.$plugin_name.'" href="plugins/'.$plugin_name.'/'.$plugin_name.'.jnlp">
                    <ext-download ext-part="'.$plugin_name.'" download="lazy"/>
                </extension>
                ';
            }
        }
    ?>

</resources>

<application-desc main-class="org.objectweb.salome_tmf.codex.ihm.main.SalomeTMF_JWS_Codex">
    <argument>slm.projectName=<?=$slm_project_name?></argument>
    <argument>slm.userLogin=<?=$slm_user_name?></argument>
    <argument>slm.locale=<?=$slm_locale?></argument>
    <!-- Codendi Config -->
    <argument>cx.jri.svr.url=<?=$cx_jri_svr_url?></argument>
    <argument>cx.svr.url=<?=$cx_svr_url?></argument>
    <argument>cx.session.hash=<?=$cx_session_hash?></argument>
    <argument>cx.user.pass=<?=$cx_user_pass?></argument>
    <argument>cx.proxy.url=<?=$cx_proxy_url?></argument>
    <argument>cx.proxy.user=<?=$cx_proxy_user?></argument>
    <argument>cx.proxy.pass=<?=$cx_proxy_pass?></argument>
</application-desc>

</jnlp>
