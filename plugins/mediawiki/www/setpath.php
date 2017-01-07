<?php

require_once 'common/Config/LocalIncFinder.php';
$locar_inc_finder = new Config_LocalIncFinder();
$local_inc = $locar_inc_finder->getLocalIncPath();

require_once $local_inc;
require_once $GLOBALS['db_config_file'];

require_once 'common/dao/CodendiDataAccess.class.php';
require_once 'common/dao/include/DataAccessObject.class.php';
require_once 'common/Config/ForgeConfig.php';

require_once __DIR__.'/../include/autoload.php';

$dao = new MediawikiSiteAdminResourceRestrictorDao();

if(isset($GLOBALS['TULEAP_MW_PROJECT'])) {
    $wiki_name = $GLOBALS['TULEAP_MW_PROJECT'];
} else {
    $uri = explode('/', $_SERVER['REQUEST_URI']);
    $wiki_name = $uri[4];
}

if (file_exists('/usr/share/mediawiki-tuleap-123') && $dao->isMediawiki123(MediawikiSiteAdminResourceRestrictor::RESOURCE_ID, $wiki_name)) {
    $mediawikipath = '/usr/share/mediawiki-tuleap-123';
} else {
    $mediawikipath = '/usr/share/mediawiki-tuleap';
}
$IP = $mediawikipath;
