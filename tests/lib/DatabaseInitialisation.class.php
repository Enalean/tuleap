<?php

class DatabaseInitialization {

    /** @var mysqli */
    protected $mysqli;

    public function __construct() {
        $this->loadConfiguration();
        $this->mysqli = mysqli_init();
        if (! $this->mysqli->real_connect($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd'])) {
            $this->mysqli = false;
        }
    }

    public function setUp() {
        $this->initDb();
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));
    }

    /**
     * Execute all statements of given file (bulk imports)
     *
     * @param String $file
     */
    protected function mysqlLoadFile($file) {
        echo "Load $file\n";
        $mysql_cmd = 'mysql -h '.$GLOBALS['sys_dbhost'].' -u'.$GLOBALS['sys_dbuser'].' -p'.$GLOBALS['sys_dbpasswd'].' '.$GLOBALS['sys_dbname'];
        $cmd = $mysql_cmd.' < '.$file;
        system($cmd);
    }

    protected function loadConfiguration() {
        if (is_file('/etc/tuleap/conf/database.inc')) {
            ForgeConfig::loadFromFile('/etc/tuleap/conf/database.inc');
        } else {
            $config_file = 'tests.inc';
            ForgeConfig::loadFromFile(dirname(__FILE__)."/../../src/etc/$config_file.dist");
            ForgeConfig::loadFromFile(dirname($this->getLocalIncPath())."/$config_file");
        }
        $GLOBALS['sys_dbhost']   = ForgeConfig::get('sys_dbhost');
        $GLOBALS['sys_dbuser']   = ForgeConfig::get('sys_dbuser');
        $GLOBALS['sys_dbpasswd'] = ForgeConfig::get('sys_dbpasswd');
        $GLOBALS['sys_dbname']   = ForgeConfig::get('sys_dbname');
    }

    protected function getLocalIncPath() {
        return getenv('CODENDI_LOCAL_INC') ? getenv('CODENDI_LOCAL_INC') : '/etc/codendi/conf/local.inc';
    }

    protected function initDb() {
        echo "Create database structure \n";

        $this->forceCreateDatabase();
        $this->mysqlLoadFile('src/db/mysql/database_structure.sql');
        $this->mysqlLoadFile('src/db/mysql/database_initvalues.sql');
        $this->mysqlLoadFile('plugins/tracker/db/install.sql');
        $this->mysqlLoadFile('plugins/graphontrackersv5/db/install.sql');
        $this->mysqlLoadFile('plugins/agiledashboard/db/install.sql');
        $this->mysqlLoadFile('plugins/cardwall/db/install.sql');
    }

    protected function forceCreateDatabase() {
        $this->mysqli->query("DROP DATABASE IF EXISTS ".$GLOBALS['sys_dbname']);
        $this->mysqli->query("CREATE DATABASE ".$GLOBALS['sys_dbname']." CHARACTER SET utf8");
    }
}