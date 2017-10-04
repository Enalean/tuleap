<?php

class DatabaseInitialization
{
    /** @var mysqli */
    protected $mysqli;

    public function __construct()
    {
        $this->loadConfiguration();
        $this->mysqli = mysqli_init();
        if (! $this->mysqli->real_connect($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd'])) {
            $this->mysqli = false;
        }
    }

    private function loadConfiguration()
    {
        ForgeConfig::loadFromFile('/etc/tuleap/conf/database.inc');

        $GLOBALS['sys_dbhost']   = ForgeConfig::get('sys_dbhost');
        $GLOBALS['sys_dbuser']   = ForgeConfig::get('sys_dbuser');
        $GLOBALS['sys_dbpasswd'] = ForgeConfig::get('sys_dbpasswd');
        $GLOBALS['sys_dbname']   = ForgeConfig::get('sys_dbname');
    }
}
