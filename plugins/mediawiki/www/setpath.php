<?php

require_once __DIR__ . '/../../../src/vendor/autoload.php';
spl_autoload_register(function ($class_with_namespace) {
    $iterator = new AppendIterator();
    $iterator->append(
        new RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ .'/../../../src/common/dao/include/', \FilesystemIterator::SKIP_DOTS)
        )
    );
    $iterator->append(
        new RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ .'/../../../src/common/DB/', \FilesystemIterator::SKIP_DOTS)
        )
    );
    $iterator->append(
        new RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ .'/../../../src/common/Config/', \FilesystemIterator::SKIP_DOTS)
        )
    );

    $class_with_exploded_namespace = explode('\\', $class_with_namespace);
    $class                         = array_pop($class_with_exploded_namespace);
    foreach ($iterator as $file_info) {
        $file_name = $file_info->getFilename();
        if ($class . '.php' === $file_name || $class . '.class.php' === $file_name) {
            require_once $file_info->getPathname();
            return;
        }
    }
});
require_once __DIR__ . '/../../../src/common/dao/CodendiDataAccess.class.php';
require_once __DIR__ . '/../../../src/common/Config/LocalIncFinder.php';
$locar_inc_finder = new Config_LocalIncFinder();
$local_inc = $locar_inc_finder->getLocalIncPath();

require_once $local_inc;
require_once $GLOBALS['db_config_file'];

ForgeConfig::loadFromFile($local_inc);
ForgeConfig::loadFromFile($GLOBALS['db_config_file']);

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
