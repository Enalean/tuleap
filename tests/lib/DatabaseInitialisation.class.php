<?php

class DatabaseInitialization {
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
        $this->mysqli->select_db(Config::get('sys_dbname'));
    }

    /**
     * Execute all statements of given file (bulk imports)
     *
     * @param String $file
     */
    protected function mysqlLoadFile($file) {
        $mysql_cmd = 'mysql -h '.$GLOBALS['sys_dbhost'].' -u'.$GLOBALS['sys_dbuser'].' -p'.$GLOBALS['sys_dbpasswd'].' '.$GLOBALS['sys_dbname'];
        $cmd = $mysql_cmd.' < '.$file;
        system($cmd);
    }

    private function loadConfiguration() {
        $config_file = 'tests.inc';
        Config::load(dirname(__FILE__)."/../../src/etc/$config_file.dist");
        Config::load(dirname($this->getLocalIncPath())."/$config_file");
        $GLOBALS['sys_dbhost']   = Config::get('sys_dbhost');
        $GLOBALS['sys_dbuser']   = Config::get('sys_dbuser');
        $GLOBALS['sys_dbpasswd'] = Config::get('sys_dbpasswd');
        $GLOBALS['sys_dbname']   = Config::get('sys_dbname');
    }

    private function getLocalIncPath() {
        return getenv('CODENDI_LOCAL_INC') ? getenv('CODENDI_LOCAL_INC') : '/etc/codendi/conf/local.inc';
    }

    private function initDb() {
        $this->forceCreateDatabase();
        $this->mysqlLoadFile('src/db/mysql/database_structure.sql');
        $this->mysqlLoadFile('src/db/mysql/database_initvalues.sql');
        $this->mysqlLoadFile('src/db/mysql/trackerv3values.sql');
        $this->mysqlLoadFile('plugins/tracker_date_reminder/db/install.sql');
        $this->mysqlLoadFile('plugins/tracker_date_reminder/db/examples.sql');
        $this->mysqlLoadFile('plugins/graphontrackers/db/install.sql');
        $this->mysqlLoadFile('plugins/graphontrackers/db/initvalues.sql');
        $this->mysqlLoadFile('plugins/tracker/db/install.sql');
        $this->mysqlLoadFile('plugins/graphontrackersv5/db/install.sql');
    }

    private function forceCreateDatabase() {
        $this->mysqli->query("DROP DATABASE IF EXISTS ".$GLOBALS['sys_dbname']);
        $this->mysqli->query("CREATE DATABASE ".$GLOBALS['sys_dbname']);
    }
}