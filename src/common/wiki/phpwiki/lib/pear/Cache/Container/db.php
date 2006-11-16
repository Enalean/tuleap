<?php
// +----------------------------------------------------------------------+
// | PEAR :: Cache                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Ulf Wendel <ulf.wendel@phpdoc.de>                           |
// |          Sebastian Bergmann <sb@sebastian-bergmann.de>               |
// |          Chuck Hagenbuch <chuck@horde.org>                           |
// +----------------------------------------------------------------------+
//
// $Id: db.php,v 1.1 2004/06/21 08:39:38 rurban Exp $

require_once 'DB.php';
require_once 'Cache/Container.php';

/**
* PEAR/DB Cache Container.
*
* WARNING: Other systems might or might not support certain datatypes of
* the tables shown. As far as I know there's no large binary
* type in SQL-92 or SQL-99. Postgres seems to lack any
* BLOB or TEXT type, for MS-SQL you could use IMAGE, don't know
* about other databases. Please add sugestions for other databases to
* the inline docs.
*
* The field 'changed' has no meaning for the Cache itself. It's just there
* because it's a good idea to have an automatically updated timestamp
* field for debugging in all of your tables.
*
* For _MySQL_ you need this DB table:
*
* CREATE TABLE cache (
*   id          CHAR(32) NOT NULL DEFAULT '',
*   cachegroup  VARCHAR(127) NOT NULL DEFAULT '',
*   cachedata   BLOB NOT NULL DEFAULT '',
*   userdata    VARCHAR(255) NOT NULL DEFAUL '',
*   expires     INT(9) NOT NULL DEFAULT 0,
*
*   changed     TIMESTAMP(14) NOT NULL,
*
*   INDEX (expires),
*   PRIMARY KEY (id, cachegroup)
* )
*
* @author   Sebastian Bergmann <sb@sebastian-bergmann.de>
* @version  $Id: db.php,v 1.1 2004/06/21 08:39:38 rurban Exp $
* @package  Cache
*/
class Cache_Container_db extends Cache_Container {

    /**
    * Name of the DB table to store caching data
    *
    * @see  Cache_Container_file::$filename_prefix
    */
    var $cache_table = '';

    /**
    * PEAR DB dsn to use.
    *
    * @var  string
    */
    var $dsn = '';

    /**
    * PEAR DB object
    *
    * @var  object PEAR_DB
    */
    var $db;

    function Cache_Container_db($options)
    {
        if (!is_array($options) || !isset($options['dsn'])) {
            return new Cache_Error('No dsn specified!', __FILE__, __LINE__);
        }

        $this->setOptions($options,  array_merge($this->allowed_options, array('dsn', 'cache_table')));

        if (!$this->dsn)
            return new Cache_Error('No dsn specified!', __FILE__, __LINE__);

        $this->db = DB::connect($this->dsn, true);
        if (DB::isError($this->db)) {
            return new Cache_Error('DB::connect failed: ' . DB::errorMessage($this->db), __FILE__, __LINE__);
        } else {
            $this->db->setFetchMode(DB_FETCHMODE_ASSOC);
        }
    }

    function fetch($id, $group)
    {
        $query = sprintf("SELECT cachedata, userdata, expires FROM %s WHERE id = '%s' AND cachegroup = '%s'",
                         $this->cache_table,
                         addslashes($id),
                         addslashes($group)
                         );

        $res = $this->db->query($query);

        if (DB::isError($res))
            return new Cache_Error('DB::query failed: ' . DB::errorMessage($res), __FILE__, __LINE__);

        $row = $res->fetchRow();
        if (is_array($row))
            $data = array($row['expires'], $this->decode($row['cachedata']), $row['userdata']);
        else
            $data = array(NULL, NULL, NULL);

        // last used required by the garbage collection
        // WARNING: might be MySQL specific
        $query = sprintf("UPDATE %s SET changed = (NOW() + 0) WHERE id = '%s' AND cachegroup = '%s'",
                            $this->cache_table,
                            addslashes($id),
                            addslashes($group)
                          );

        $res = $this->db->query($query);

        if (DB::isError($res))
            return new Cache_Error('DB::query failed: ' . DB::errorMessage($res), __FILE__, __LINE__);

        return $data;
    }

    /**
    * Stores a dataset.
    *
    * WARNING: we use the SQL command REPLACE INTO this might be
    * MySQL specific. As MySQL is very popular the method should
    * work fine for 95% of you.
    */
    function save($id, $data, $expires, $group, $userdata)
    {
        $this->flushPreload($id, $group);

        $query = sprintf("REPLACE INTO %s (userdata, cachedata, expires, id, cachegroup) VALUES ('%s', '%s', %d, '%s', '%s')",
                         $this->cache_table,
                         addslashes($userdata),
                         addslashes($this->encode($data)),
                          $this->getExpiresAbsolute($expires) ,
                         addslashes($id),
                         addslashes($group)
                        );

        $res = $this->db->query($query);

        if (DB::isError($res)) {
            return new Cache_Error('DB::query failed: ' . DB::errorMessage($res) , __FILE__, __LINE__);
        }
    }

    function remove($id, $group)
    {
        $this->flushPreload($id, $group);

        $query = sprintf("DELETE FROM %s WHERE id = '%s' and cachegroup = '%s'",
                         $this->cache_table,
                         addslashes($id),
                         addslashes($group)
                        );

        $res = $this->db->query($query);

        if (DB::isError($res))
            return new Cache_Error('DB::query failed: ' . DB::errorMessage($res), __FILE__, __LINE__);
    }

    function flush($group = '')
    {
        $this->flushPreload();

         if ($group) {
            $query = sprintf("DELETE FROM %s WHERE cachegroup = '%s'", $this->cache_table, addslashes($group));
        } else {
            $query = sprintf("DELETE FROM %s", $this->cache_table);
        }

        $res = $this->db->query($query);

        if (DB::isError($res))
            return new Cache_Error('DB::query failed: ' . DB::errorMessage($res), __FILE__, __LINE__);
    }

    function idExists($id, $group)
    {
        $query = sprintf("SELECT id FROM %s WHERE ID = '%s' AND cachegroup = '%s'",
                         $this->cache_table,
                         addslashes($id),
                         addslashes($group)
                        );

        $res = $this->db->query($query);

        if (DB::isError($res))
            return new Cache_Error('DB::query failed: ' . DB::errorMessage($res), __FILE__, __LINE__);

        $row = $res->fetchRow();

        if (is_array($row)) {
            return true;
        } else {
            return false;
        }
    }

    function garbageCollection($maxlifetime)
    {
        $this->flushPreload();

        $query = sprintf('DELETE FROM %s WHERE (expires <= %d AND expires > 0) OR changed <= %d',
                         $this->cache_table,
                         time(),
                         time() - $maxlifetime
                       );

        $res = $this->db->query($query);

        $query = sprintf('select sum(length(cachedata)) as CacheSize from %s',
                         $this->cache_table
                       );
        $cachesize = $this->db->GetOne($query);
        if (DB::isError($cachesize)) {
            return new Cache_Error('DB::query failed: ' . DB::errorMessage($cachesize), __FILE__, __LINE__);
        }

        //if cache is to big.
        if ($cachesize > $this->highwater)
        {
            //find the lowwater mark.
            $query = sprintf('select length(cachedata) as size, changed from %s order by changed DESC',
                                     $this->cache_table
                       );
            $res = $this->db->query($query);
            if (DB::isError($res)) {
                return new Cache_Error('DB::query failed: ' . DB::errorMessage($res), __FILE__, __LINE__);
            }

            $numrows = $this->db->numRows($res);
            $keep_size = 0;
            while ($keep_size < $this->lowwater && $numrows--) {
                $entry = $res->fetchRow(DB_FETCHMODE_ASSOC);
                $keep_size += $entry['size'];
            }

            //delete all entries, which were changed before the "lowwwater mark"
            $query = sprintf('delete from %s where changed <= '.($entry['changed'] ? $entry['changed'] : 0),
                                     $this->cache_table
                                   );
            $res = $this->db->query($query);
        }

        if (DB::isError($res)) {
            return new Cache_Error('DB::query failed: ' . DB::errorMessage($res), __FILE__, __LINE__);
        }
    }

}
?>
