<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet
 *
 * ForgeUpgrade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ForgeUpgrade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ForgeUpgrade. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'src/db/driver/Abstract.php';

class ForgeUpgrade_Db_Driver extends ForgeUpgrade_Db_Driver_Abstract
{
    protected $pdo;
    protected $dsn;
    protected $user;
    protected $password;

    protected $platform_name = "tuleap";
    protected $env_variable_name = "TULEAP_LOCAL_INC";

    protected function initOptions()
    {
        if (!$this->dsn) {
            $localInc = $this->getLocalInc();
            if (is_file($localInc)) {
                include $localInc;
                include $db_config_file;

                $port   = '';
                $socket = '';

                if (strpos($sys_dbhost, ':') !== false) {
                    list($host, $details) = explode(':', $sys_dbhost);
                    if (is_numeric($details)) {
                        $port = ';port=' . $details;
                    } else {
                        $socket = ';unix_socket=' . $socket;
                    }
                } else {
                    $host   = $sys_dbhost;
                }

                $this->dsn      = 'mysql:host=' . $host . $socket . $port . ';dbname=' . $sys_dbname;
                $this->user     = $sys_dbuser;
                $this->password = $sys_dbpasswd;
            } else {
                throw new Exception($this->getErrorLocalIncMessage());
            }
        }
    }

    private function getLocalInc()
    {
        return getenv($this->env_variable_name) ? getenv($this->env_variable_name) : '/etc/' . $this->platform_name . '/conf/local.inc';
    }

    private function getErrorLocalIncMessage()
    {
        return 'Unable to find a valid local.inc for ' . $this->platform_name . ', please check ' . $this->env_variable_name . ' environment variable';
    }

    /**
     * Setup the PDO object to be used for DB connexion
     *
     * The DB connexion will be used to store buckets execution log.
     *
     * @return PDO
     */
    public function getPdo()
    {
        if (!$this->pdo) {
            $this->initOptions();
            $this->pdo = new PDO(
                $this->dsn,
                $this->user,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND =>  "SET NAMES 'UTF8'")
            );
            //$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->pdo;
    }

    /**
     * Return a PDO logger appender that will reference the given bucket id
     *
     * @param ForgeUpgrade_Bucket $bucket The bucket
     *
     * @return LoggerAppenderPDO
     */
    public function getBucketLoggerAppender(ForgeUpgrade_Bucket $bucket)
    {
        $this->initOptions();

        $logger = new LoggerAppenderPDO();
        $logger->setUser($this->user);
        $logger->setPassword($this->password);
        $logger->setDSN($this->dsn);
        $logger->setTable('forge_upgrade_log');
        $logger->setInsertSql('INSERT INTO forge_upgrade_log (id, bucket_id, timestamp, logger, level, message, thread, file, line) VALUES (NULL,' . $bucket->getId() . ',?,?,?,?,?,?,?)');
        $logger->setInsertPattern('%d,%c,%p,%m,%t,%F,%L');
        $logger->activateOptions();

        return $logger;
    }
}
