<?php
/**
 * ARC2 RDF Store Table Manager
 *
 * @license   http://arc.semsol.org/license
 * @author    Benjamin Nowack
 * @version   2010-06-21
 *
*/

ARC2::inc('Store');

class ARC2_StoreTableManager extends ARC2_Store {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_StoreTableManager($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* db_con */
    parent::__init();
    $this->engine_type = $this->v('store_engine_type', 'MyISAM', $this->a);
  }

  /*  */
  
  function getTableOptionsCode() {
    $v = $this->getDBVersion();
    $r = "";
    $r .= (($v < '04-01-00') && ($v >= '04-00-18')) ? 'ENGINE' : (($v >= '04-01-02') ? 'ENGINE' : 'TYPE');
    $r .= "=" . $this->engine_type;
    $r .= ($v >= '04-00-00') ? " CHARACTER SET utf8" : "";
    $r .= ($v >= '04-01-00') ? " COLLATE utf8_unicode_ci" : "";
    $r .= " DELAY_KEY_WRITE = 1";
    return $r;
  }
  
  /*  */
  
  function createTables() {
    $con = $this->getDBCon();
    if(!$this->createTripleTable()) {
      return $this->addError('Could not create "triple" table (' . mysql_error($con) . ').');
    }
    if(!$this->createG2TTable()) {
      return $this->addError('Could not create "g2t" table (' . mysql_error($con) . ').');
    }
    if(!$this->createID2ValTable()) {
      return $this->addError('Could not create "id2val" table (' . mysql_error($con) . ').');
    }
    if(!$this->createS2ValTable()) {
      return $this->addError('Could not create "s2val" table (' . mysql_error($con) . ').');
    }
    if(!$this->createO2ValTable()) {
      return $this->addError('Could not create "o2val" table (' . mysql_error($con) . ').');
    }
    if(!$this->createSettingTable()) {
      return $this->addError('Could not create "setting" table (' . mysql_error($con) . ').');
    }
    return 1;
  }
  
  /*  */
  
  function createTripleTable($suffix = 'triple') {
    /* keep in sync with merge def in StoreQueryHandler ! */
    $indexes = $this->v('store_indexes', array('sp (s,p)', 'os (o,s)', 'po (p,o)'), $this->a);
    $index_code = $indexes ? 'KEY ' . join(', KEY ',  $indexes) . ', ' : '';
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . $suffix . " (
        t mediumint UNSIGNED NOT NULL,
        s mediumint UNSIGNED NOT NULL,
        p mediumint UNSIGNED NOT NULL,
        o mediumint UNSIGNED NOT NULL,
        o_lang_dt mediumint UNSIGNED NOT NULL,
        o_comp char(35) NOT NULL,                   /* normalized value for ORDER BY operations */
        s_type tinyint(1) NOT NULL default 0,       /* uri/bnode => 0/1 */
        o_type tinyint(1) NOT NULL default 0,       /* uri/bnode/literal => 0/1/2 */
        misc tinyint(1) NOT NULL default 0,         /* temporary flags */
        UNIQUE KEY (t), " . $index_code . " KEY (misc)
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql, $this->getDBCon());
  }

  function extendTripleTableColumns($suffix = 'triple') {
    $sql = "
      ALTER TABLE " . $this->getTablePrefix() . $suffix . "
      MODIFY t int(10) UNSIGNED NOT NULL,
      MODIFY s int(10) UNSIGNED NOT NULL,
      MODIFY p int(10) UNSIGNED NOT NULL,
      MODIFY o int(10) UNSIGNED NOT NULL,
      MODIFY o_lang_dt int(10) UNSIGNED NOT NULL
    ";
    return mysql_query($sql, $this->getDBCon());
  }
  
  /*  */
  
  function createG2TTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "g2t (
        g mediumint UNSIGNED NOT NULL,
        t mediumint UNSIGNED NOT NULL,
        UNIQUE KEY gt (g,t), KEY tg (t,g)
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql, $this->getDBCon());
  }  
  
  function extendG2tTableColumns($suffix = 'g2t') {
    $sql = "
      ALTER TABLE " . $this->getTablePrefix() . $suffix . "
      MODIFY g int(10) UNSIGNED NOT NULL,
      MODIFY t int(10) UNSIGNED NOT NULL
    ";
    return mysql_query($sql, $this->getDBCon());
  }

  /*  */
  
  function createID2ValTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "id2val (
        id mediumint UNSIGNED NOT NULL,
        misc tinyint(1) NOT NULL default 0,
        val text NOT NULL,
        val_type tinyint(1) NOT NULL default 0,     /* uri/bnode/literal => 0/1/2 */
        UNIQUE KEY (id,val_type), KEY v (val(64))
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql, $this->getDBCon());
  }  
  
  function extendId2valTableColumns($suffix = 'id2val') {
    $sql = "
      ALTER TABLE " . $this->getTablePrefix() . $suffix . "
      MODIFY id int(10) UNSIGNED NOT NULL
    ";
    return mysql_query($sql, $this->getDBCon());
  }

  /*  */
  
  function createS2ValTable() {
    //$indexes = 'UNIQUE KEY (id), KEY vh (val_hash), KEY v (val(64))';
    $indexes = 'UNIQUE KEY (id), KEY vh (val_hash)';
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "s2val (
        id mediumint UNSIGNED NOT NULL,
        misc tinyint(1) NOT NULL default 0,
        val_hash char(32) NOT NULL,
        val text NOT NULL,
        " . $indexes . "
      ) " . $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql, $this->getDBCon());
  }  
  
  function extendS2valTableColumns($suffix = 's2val') {
    $sql = "
      ALTER TABLE " . $this->getTablePrefix() . $suffix . "
      MODIFY id int(10) UNSIGNED NOT NULL
    ";
    return mysql_query($sql, $this->getDBCon());
  }

  /*  */
  
  function createO2ValTable() {
    /* object value index, e.g. "KEY v (val(64))" and/or "FULLTEXT KEY vft (val)" */
    $val_index = $this->v('store_object_index', 'KEY v (val(64))', $this->a);
    if ($val_index) $val_index = ', ' . ltrim($val_index, ',');
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "o2val (
        id mediumint UNSIGNED NOT NULL,
        misc tinyint(1) NOT NULL default 0,
        val_hash char(32) NOT NULL,
        val text NOT NULL,
        UNIQUE KEY (id), KEY vh (val_hash)" . $val_index . "
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql, $this->getDBCon());
  }  
  
  function extendO2valTableColumns($suffix = 'o2val') {
    $sql = "
      ALTER TABLE " . $this->getTablePrefix() . $suffix . "
      MODIFY id int(10) UNSIGNED NOT NULL
    ";
    return mysql_query($sql, $this->getDBCon());
  }

  /*  */
  
  function createSettingTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "setting (
        k char(32) NOT NULL,
        val text NOT NULL,
        UNIQUE KEY (k)
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql, $this->getDBCon());
  }  
  
  /*  */

  function extendColumns() {
    $con = $this->getDBCon();
    $tbl_prefix = $this->getTablePrefix();
    $tbls = $this->getTables();
    foreach ($tbls as $suffix) {
      if (preg_match('/^(triple|g2t|id2val|s2val|o2val)/', $suffix, $m)) {
        $mthd = 'extend' . ucfirst($m[1]) . 'TableColumns';
        $this->$mthd($suffix);
      }
    }
  }

  /*  */

  function splitTables() {
    $old_ps = $this->getSetting('split_predicates', array());
    $new_ps = $this->retrieveSplitPredicates();
    $add_ps = array_diff($new_ps, $old_ps);
    $del_ps = array_diff($old_ps, $new_ps);
    $final_ps = array();
    foreach ($del_ps as $p) {
      if (!$this->unsplitPredicate($p)) $final_ps[] = $p;
    }
    foreach ($add_ps as $p) {
      if ($this->splitPredicate($p)) $final_ps[] = $p;
    }
    $this->setSetting('split_predicates', $new_ps);
  }

  function unsplitPredicate($p) {
    $suffix = 'triple_' . abs(crc32($p));
    $old_tbl = $this->getTablePrefix() . $suffix;
    $new_tbl = $this->getTablePrefix() . 'triple';
    $p_id = $this->getTermID($p, 'p');
    $con = $this->getDBCon();
    $sql = '
      INSERT IGNORE INTO ' . $new_tbl .'
      SELECT * FROM ' . $old_tbl . ' WHERE ' . $old_tbl . '.p = ' . $p_id . '
    ';
    if ($rs = mysql_query($sql, $con)) {
      mysql_query('DROP TABLE ' . $old_tbl, $con);
      return 1;
    }
    else {
      return 0;
    }
  }

  function splitPredicate($p) {
    $suffix = 'triple_' . abs(crc32($p));
    $this->createTripleTable($suffix);
    $old_tbl = $this->getTablePrefix() . 'triple';
    $new_tbl = $this->getTablePrefix() . $suffix;
    $p_id = $this->getTermID($p, 'p');
    $con = $this->getDBCon();
    $sql = '
      INSERT IGNORE INTO ' . $new_tbl .'
      SELECT * FROM ' . $old_tbl . ' WHERE ' . $old_tbl . '.p = ' . $p_id . '
    ';
    if ($rs = mysql_query($sql, $con)) {
      mysql_query('DELETE FROM ' . $old_tbl . ' WHERE ' . $old_tbl . '.p = ' . $p_id, $con);
      return 1;
    }
    else {
      mysql_query('DROP TABLE ' . $new_tbl, $con);
      return 0;
    }
  }

  function retrieveSplitPredicates() {
    $r = $this->split_predicates;
    $limit = $this->max_split_tables - count($r);
    $q = 'SELECT ?p COUNT(?p) AS ?pc WHERE { ?s ?p ?o } GROUP BY ?p ORDER BY DESC(?pc) LIMIT ' . $limit;
    $rows = $this->query($q, 'rows');
    foreach ($rows as $row) {
      $r[] = $row['p'];
    }
    return $r;
  }

  /*  */

}
