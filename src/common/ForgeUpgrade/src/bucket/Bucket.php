<?php
/**
 * Copyright (c) Enalean SAS, 2011-Present. All Rights Reserved.
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

require_once 'UpgradeNotCompleteException.php';
require_once 'ApiNotFoundException.php';
require_once 'db/Db.php';

/**
 * A bucket is a migration scenario
 */
abstract class ForgeUpgrade_Bucket
{
    protected $log;
    protected $api;

    protected $dryRun = true;
    protected $path   = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->log = Logger::getLogger(self::class);
    }

    public function setAllApi(array $api)
    {
        $this->api = $api;
    }

    public function setApi($api)
    {
        $this->api[get_class($api)] = $api;
    }

    public function getApi($key)
    {
        if (isset($this->api[$key])) {
            return $this->api[$key];
        }
        throw new ForgeUpgrade_Bucket_Exception_ApiNotFound('API "' . $key . '" not found');
    }

    /**
     * Return a string with the description of the upgrade
     *
     * @return String
     */
    abstract public function description();

    /**
     * Allow to define a dependency list
     *
     * @return Array
     */
    public function dependsOn()
    {
    }

    /**
     * Ensure the package is OK before running Up method
     *
     * Use this method add your own pre-conditions.
     * This method aims to verify stuff needed by the up method it doesn't
     * target a global validation of the application.
     *
     * This method MUST be safe (doesn't modify the system and runnable several
     * time)
     *
     * If an error is detected, this method should throw an Exception and this
     * will stop further processing. So only throw an Exception if you detect
     * that something will go wrong during 'up' method execution.
     * For instance:
     * Your 'up' method creates a table but this table already exists.
     * -> This should not throw an exception.
     * -> But if:
     *    - your up method rely on a given field in the table
     *    - this field is not present in the existing table
     *    - you doesn't create the field in 'up'
     * -> This should throw an exception
     */
    public function preUp()
    {
    }

    /**
     * Perform the upgrade
     */
    abstract public function up();

    /**
     * Ensure the package is OK after running Up method
     *
     * Use this method add your own post-conditions.
     * This method aims to verify that what the migration should bring is here.
     *
     * This method MUST be safe (doesn't modify the system and runnable several
     * time)
     *
     * If an error is detected, this method should throw an Exception
     */
    public function postUp()
    {
    }






    public function setDryRun($mode)
    {
        $this->dryRun = ($mode === true);
    }

    public function getDryRun()
    {
        return $this->dryRun();
    }

    public function setLoggerParent(Logger $log)
    {
        $this->log->setParent($log);
        //$this->db->setLoggerParent($this->log);
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
