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

use Psr\Log\LoggerInterface;

/**
 * Wrap access to the DB and provide a set of convenient tools to write
 * DB upgrades
 */
class ForgeUpgrade_Bucket_Db // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $dbh;

    protected $log;

    /**
     * Constructor
     *
     * @param PDO $dbh PDO database handler
     */
    public function __construct(PDO $dbh, LoggerInterface $logger)
    {
        $this->dbh = $dbh;
        $this->log = $logger;
    }

    /**
     * Return true if the given table name already exists into the database
     *
     * @param String $tableName Table name
     *
     * @return bool
     */
    public function tableNameExists($tableName)
    {
        $sql = 'SHOW TABLES LIKE ' . $this->dbh->quote($tableName);
        $res = $this->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return true if the given column name already exists into the database
     *
     * @param String $tableName  Table name
     * @param String $columnName Column name
     *
     * @return bool
     */
    public function columnNameExists($tableName, $columnName)
    {
        $sql = "SHOW COLUMNS FROM `$tableName` LIKE " . $this->dbh->quote($columnName);

        $res = $this->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return true if the given index name on the table already exists into the database
     *
     * @param String $tableName Table name
     * @param String $index     Index
     *
     * @return bool
     */
    public function indexNameExists($tableName, $index)
    {
        $sql = "SHOW INDEX FROM `$tableName` WHERE Key_name LIKE " . $this->dbh->quote($index);
        $res = $this->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return true if a primary key already exists on this table
     *
     * @param String $tableName      Table name
     *
     * @return bool
     */
    public function primaryKeyExists($tableName)
    {
        $sql = "SHOW INDEXES FROM `$tableName` WHERE Key_name = 'PRIMARY'";
        $res = $this->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create new table
     *
     * Create new table if not already exists and report errors.
     *
     * @param String             $tableName Table name
     * @param String             $sql       The create table statement
     */
    public function createTable($tableName, $sql)
    {
        $this->log->info('Add table ' . $tableName);
        if (! $this->tableNameExists($tableName)) {
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occured adding table ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
            $this->log->info($tableName . ' successfully added');
        } else {
            $this->log->info($tableName . ' already exists');
        }
    }

    /**
     * Delete table
     *
     * Delete table if exists and report errors.
     *
     * @param String             $tableName Table name
     * @param String             $sql       The delete table statement (optionnal)
     */
    public function dropTable($tableName, $sql = '')
    {
        $this->log->info('Delete table ' . $tableName);
        if ($this->tableNameExists($tableName)) {
            if (! $sql) {
                $sql = 'DROP TABLE `' . $tableName . '`';
            }
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occured deleting table ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
            $this->log->info($tableName . ' successfully deleted');
        } else {
            $this->log->info($tableName . ' not exists');
        }
    }

    /**
     * Add index
     *
     * Alter table to add index and report errors.
     *
     * @param String             $tableName Table name
     * @param String             $index     The index
     * @param String             $sql       The add index statement
     */
    public function addIndex($tableName, $index, $sql)
    {
        $this->log->info('Add index ' . $tableName);
        if (! $this->indexNameExists($tableName, $index)) {
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occured adding index to ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
            $this->log->info($index . ' successfully added index');
        } else {
            $this->log->info($index . ' already exists');
        }
    }

    /**
     * Return true if given table has a given propertie
     *
     * @param String $tableName   Table name
     * @param String $schema      Schema
     * @param String $property    Field
     *
     * @return bool
     */
    public function propertyExists($tableName, $schema, $property)
    {
        $sql = 'SELECT table_name FROM ' . $schema . ' WHERE ' . $property . ' AND table_name LIKE ' . $this->dbh->quote($tableName);
        $res = $this->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Alter table
     *
     * Alter table to modify field value and report errors.
     *
     * @param String             $tableName   Table name
     * @param String             $schema      Schema
     * @param String             $property    Field
     * @param String             $sql         The alter table statement
     */
    public function alterTable($tableName, $schema, $property, $sql)
    {
        $this->log->info('Alter table ' . $tableName);
        if (! $this->propertyExists($tableName, $schema, $property)) {
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occured while altering ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
            $this->log->info($tableName . ' successfully altered table');
        } else {
            $this->log->info($tableName . ' already modified');
        }
    }

    /**
     * Add primary key
     *
     * Alter table to add primary key and report errors.
     *
     * @param String             $tableName      Table name
     * @param String             $primaryKey     The primary key
     * @param String             $sql            The add pk statement
     */
    public function addPrimaryKey($tableName, $primaryKey, $sql)
    {
        $this->log->info('Add primary key ' . $tableName);
        if (! $this->primaryKeyExists($tableName)) {
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occured adding primary key to ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
            $this->log->info($primaryKey . ' successfully added pk');
        } else {
            $this->log->info($primaryKey . ' pk already exists');
        }
    }
}
