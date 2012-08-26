<?php
/**
 * ARC2 RDF Store
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license http://arc.semsol.org/license
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-06-22
*/

ARC2::inc('Class');

class ARC2_Store extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_Store($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* db_con */
    parent::__init();
    $this->table_lock = 0;
    $this->triggers = $this->v('store_triggers', array(), $this->a);
    $this->queue_queries = $this->v('store_queue_queries', 0, $this->a);
    $this->is_win = (strtolower(substr(PHP_OS, 0, 3)) == 'win') ? true : false;
    $this->max_split_tables = $this->v('store_max_split_tables', 10, $this->a);
    $this->split_predicates = $this->v('store_split_predicates', array(), $this->a);
  }

  /*  */
  
  function getName() {
    return $this->v('store_name', 'arc', $this->a);
  }

  function getTablePrefix() {
    if (!isset($this->tbl_prefix)) {
      $r = $this->v('db_table_prefix', '', $this->a);
      $r .= $r ? '_' : '';
      $r .= $this->getName() . '_';
      $this->tbl_prefix = $r;
    }
    return $this->tbl_prefix;;
  }

  /*  */
  
  function createDBCon() {
    foreach (array('db_host' => 'localhost', 'db_user' => '', 'db_pwd' => '', 'db_name' => '') as $k => $v) {
      $this->a[$k] = $this->v($k, $v, $this->a);
    }
    if (!$db_con = mysql_connect($this->a['db_host'], $this->a['db_user'], $this->a['db_pwd'])) {
      return $this->addError(mysql_error());
    }
    $this->a['db_con'] =& $db_con;
    if (!mysql_select_db($this->a['db_name'], $db_con)) {
      return $this->addError(mysql_error($db_con));
    }
    if (preg_match('/^utf8/', $this->getCollation())) {
      $this->queryDB("SET NAMES 'utf8'", $db_con);
    }
    return true;
  }
  
  function getDBCon($force = 0) {
    if ($force || !isset($this->a['db_con'])) {
      if (!$this->createDBCon()) {
        return false;
      }
    }
    if (!$force && !@mysql_thread_id($this->a['db_con'])) return $this->getDBCon(1);
    return $this->a['db_con'];
  }
  
  function closeDBCon() {
    if ($this->v('db_con', false, $this->a)) {
      @mysql_close($this->a['db_con']);
    }
    unset($this->a['db_con']);
  }
  
  function getDBVersion() {
    if (!$this->v('db_version')) {
      $this->db_version = preg_match("/^([0-9]+)\.([0-9]+)\.([0-9]+)/", mysql_get_server_info($this->getDBCon()), $m) ? sprintf("%02d-%02d-%02d", $m[1], $m[2], $m[3])  : '00-00-00';
    }
    return $this->db_version;
  }
  
  /*  */
  
  function getCollation() {
    $rs = $this->queryDB('SHOW TABLE STATUS LIKE "' . $this->getTablePrefix(). 'setting"', $this->getDBCon());
    return ($rs && ($row = mysql_fetch_array($rs)) && isset($row['Collation'])) ? $row['Collation'] : '';
  }

  function getColumnType() {
    if (!$this->v('column_type')) {
      $tbl = $this->getTablePrefix() . 'g2t';
      $rs = $this->queryDB('SHOW COLUMNS FROM ' . $tbl . ' LIKE "t"', $this->getDBCon());
      $row = $rs ? mysql_fetch_array($rs) : array('Type' => 'mediumint');
      $this->column_type = preg_match('/mediumint/', $row['Type']) ? 'mediumint' : 'int';
    }
    return $this->column_type;
  }

  /*  */

  function hasHashColumn($tbl) {
    $var_name = 'has_hash_column_' . $tbl;
    if (!isset($this->$var_name)) {
      $tbl = $this->getTablePrefix() . $tbl;
      $rs = $this->queryDB('SHOW COLUMNS FROM ' . $tbl . ' LIKE "val_hash"', $this->getDBCon());
      $this->$var_name = ($rs && mysql_fetch_array($rs));
    }
    return $this->$var_name;
  }

  /*  */

  function hasFulltextIndex() {
    if (!isset($this->has_fulltext_index)) {
      $this->has_fulltext_index = 0;
      $tbl = $this->getTablePrefix() . 'o2val';
      $rs = $this->queryDB('SHOW INDEX FROM ' . $tbl, $this->getDBCon());
      while ($row = mysql_fetch_array($rs)) {
        if ($row['Column_name'] != 'val') continue;
        if ($row['Index_type'] != 'FULLTEXT') continue;
        $this->has_fulltext_index = 1;
        break;
      }
    }
    return $this->has_fulltext_index;
  }

  function enableFulltextSearch() {
    if ($this->hasFulltextIndex()) return 1;
    $tbl = $this->getTablePrefix() . 'o2val';
    $this->queryDB('CREATE FULLTEXT INDEX vft ON ' . $tbl . '(val(128))', $this->getDBCon(), 1);
  }

  function disableFulltextSearch() {
    if (!$this->hasFulltextIndex()) return 1;
    $tbl = $this->getTablePrefix() . 'o2val';
    $this->queryDB('DROP INDEX vft ON ' . $tbl, $this->getDBCon());
  }

  /*  */

  function countDBProcesses() {
    return ($rs = $this->queryDB('SHOW PROCESSLIST', $this->getDBCon())) ? mysql_num_rows($rs) : 0;
  }
  
  /*  */

  function getTables() {
    return array('triple', 'g2t', 'id2val', 's2val', 'o2val', 'setting');
  }  
  
  /*  */

  function isSetUp() {
    if (($con = $this->getDBCon())) {
      $tbl = $this->getTablePrefix() . 'setting';
      return $this->queryDB("SELECT 1 FROM " . $tbl . " LIMIT 0", $con) ? 1 : 0;
    }
  }
  
  function setUp($force = 0) {
    if (($force || !$this->isSetUp()) && ($con = $this->getDBCon())) {
      if ($this->getDBVersion() < '04-00-04') {
        /* UPDATE + JOINs */
        return $this->addError('MySQL version not supported. ARC requires version 4.0.4 or higher.');
      }
      ARC2::inc('StoreTableManager');
      $mgr = new ARC2_StoreTableManager($this->a, $this);
      $mgr->createTables();
    }
  }

  function extendColumns() {
    ARC2::inc('StoreTableManager');
    $mgr = new ARC2_StoreTableManager($this->a, $this);
    $mgr->extendColumns();
    $this->column_type = 'int';
  }

  function splitTables() {
    ARC2::inc('StoreTableManager');
    $mgr = new ARC2_StoreTableManager($this->a, $this);
    $mgr->splitTables();
  }
  
  /*  */
  
  function hasSetting($k) {
    $tbl = $this->getTablePrefix() . 'setting';
    $sql = "SELECT val FROM " . $tbl . " WHERE k = '" .md5($k). "'";
    $rs = $this->queryDB($sql, $this->getDBCon());
    return ($rs && ($row = mysql_fetch_array($rs))) ? 1 : 0;
  }
  
  function getSetting($k, $default = 0) {
    $tbl = $this->getTablePrefix() . 'setting';
    $sql = "SELECT val FROM " . $tbl . " WHERE k = '" .md5($k). "'";
    $rs = $this->queryDB($sql, $this->getDBCon());
    if ($rs && ($row = mysql_fetch_array($rs))) {
      return unserialize($row['val']);
    }
    return $default;
  }
  
  function setSetting($k, $v) {
    $con = $this->getDBCon();
    $tbl = $this->getTablePrefix() . 'setting';
    if ($this->hasSetting($k)) {
      $sql = "UPDATE " .$tbl . " SET val = '" . mysql_real_escape_string(serialize($v), $con) . "' WHERE k = '" . md5($k) . "'";
    }
    else {
      $sql = "INSERT INTO " . $tbl . " (k, val) VALUES ('" . md5($k) . "', '" . mysql_real_escape_string(serialize($v), $con) . "')";
    }
    return $this->queryDB($sql, $con);
  }
  
  function removeSetting($k) {
    $tbl = $this->getTablePrefix() . 'setting';
    return $this->queryDB("DELETE FROM " . $tbl . " WHERE k = '" . md5($k) . "'", $this->getDBCon());
  }
  
  function getQueueTicket() {
    if (!$this->queue_queries) return 1;
    $t = 'ticket_' . substr(md5(uniqid(rand())), 0, 10);
    $con = $this->getDBCon();
    /* lock */
    $rs = $this->queryDB('LOCK TABLES ' . $this->getTablePrefix() . 'setting WRITE', $con);
    /* queue */
    $queue = $this->getSetting('query_queue', array());
    $queue[] = $t;
    $this->setSetting('query_queue', $queue);
    $this->queryDB('UNLOCK TABLES', $con);
    /* loop */
    $lc = 0;
    $queue = $this->getSetting('query_queue', array());
    while ($queue && ($queue[0] != $t) && ($lc < 30)) {
      if ($this->is_win) {
        sleep(1);
        $lc++;
      }
      else {
         usleep(100000);
         $lc += 0.1;
      }
      $queue = $this->getSetting('query_queue', array());
    }
    return ($lc < 30) ? $t : 0;
  }
  
  function removeQueueTicket($t) {
    if (!$this->queue_queries) return 1;
    $con = $this->getDBCon();
    /* lock */
    $this->queryDB('LOCK TABLES ' . $this->getTablePrefix() . 'setting WRITE', $con);
    /* queue */
    $vals = $this->getSetting('query_queue', array());
    $pos = array_search($t, $vals);
    $queue = ($pos < (count($vals) - 1)) ? array_slice($vals, $pos + 1) : array();
    $this->setSetting('query_queue', $queue);
    $this->queryDB('UNLOCK TABLES', $con);
  }
  
  /*  */

  function reset($keep_settings = 0) {
    $con = $this->getDBCon();
    $tbls = $this->getTables();
    $prefix = $this->getTablePrefix();
    /* remove split tables */
    $ps = $this->getSetting('split_predicates', array());
    foreach ($ps as $p) {
      $tbl = 'triple_' . abs(crc32($p));
      $this->queryDB('DROP TABLE ' . $prefix . $tbl, $con);
    }
    $this->removeSetting('split_predicates');
    /* truncate tables */
    foreach ($tbls as $tbl) {
      if ($keep_settings && ($tbl == 'setting')) {
        continue;
      }
      $this->queryDB('TRUNCATE ' . $prefix . $tbl, $con);
    }
  }
  
  function drop() {
    $con = $this->getDBCon();
    $tbls = $this->getTables();
    $prefix = $this->getTablePrefix();
    foreach ($tbls as $tbl) {
      $this->queryDB('DROP TABLE ' . $prefix . $tbl, $con);
    }
  }
  
  function insert($doc, $g, $keep_bnode_ids = 0) {
    $doc = is_array($doc) ? $this->toTurtle($doc) : $doc;
    $infos = array('query' => array('url' => $g, 'target_graph' => $g));
    ARC2::inc('StoreLoadQueryHandler');
    $h =& new ARC2_StoreLoadQueryHandler($this->a, $this);
    $r = $h->runQuery($infos, $doc, $keep_bnode_ids);
    $this->processTriggers('insert', $infos);
    return $r;
  }
  
  function delete($doc, $g) {
    if (!$doc) {
      $infos = array('query' => array('target_graphs' => array($g)));
      ARC2::inc('StoreDeleteQueryHandler');
      $h =& new ARC2_StoreDeleteQueryHandler($this->a, $this);
      $r = $h->runQuery($infos);
      $this->processTriggers('delete', $infos);
      return $r;
    }
  }
  
  function replace($doc, $g, $doc_2) {
    return array($this->delete($doc, $g), $this->insert($doc_2, $g));
  }
  
  function dump() {
    ARC2::inc('StoreDumper');
    $d =& new ARC2_StoreDumper($this->a, $this);
    $d->dumpSPOG();
  }
  
  function createBackup($path, $q = '') {
    ARC2::inc('StoreDumper');
    $d =& new ARC2_StoreDumper($this->a, $this);
    $d->saveSPOG($path, $q);
  }
  
  function renameTo($name) {
    $con = $this->getDBCon();
    $tbls = $this->getTables();
    $old_prefix = $this->getTablePrefix();
    $new_prefix = $this->v('db_table_prefix', '', $this->a);
    $new_prefix .= $new_prefix ? '_' : '';
    $new_prefix .= $name . '_';
    foreach ($tbls as $tbl) {
      $rs = $this->queryDB('RENAME TABLE ' . $old_prefix . $tbl .' TO ' . $new_prefix . $tbl, $con);
      if ($er = mysql_error($con)) {
        return $this->addError($er);
      }
    }
    $this->a['store_name'] = $name;
    unset($this->tbl_prefix);
  }
  
  function replicateTo($name) {
    $conf = array_merge($this->a, array('store_name' => $name));
    $new_store = ARC2::getStore($conf);
    $new_store->setUp();
    $new_store->reset();
    $con = $this->getDBCon();
    $tbls = $this->getTables();
    $old_prefix = $this->getTablePrefix();
    $new_prefix = $new_store->getTablePrefix();
    foreach ($tbls as $tbl) {
      $rs = $this->queryDB('INSERT IGNORE INTO ' . $new_prefix . $tbl .' SELECT * FROM ' . $old_prefix . $tbl, $con);
      if ($er = mysql_error($con)) {
        return $this->addError($er);
      }
    }
    return $new_store->query('SELECT COUNT(*) AS t_count WHERE { ?s ?p ?o}', 'row');
  }
  
  /*  */
  
  function query($q, $result_format = '', $src = '', $keep_bnode_ids = 0, $log_query = 0) {
    if ($log_query) $this->logQuery($q);
    $con = $this->getDBCon();
    if (preg_match('/^dump/i', $q)) {
      $infos = array('query' => array('type' => 'dump'));
    }
    else {
      ARC2::inc('SPARQLPlusParser');
      $p = & new ARC2_SPARQLPlusParser($this->a, $this);
      $p->parse($q, $src);
      $infos = $p->getQueryInfos();
    }
    if ($result_format == 'infos') return $infos;
    $infos['result_format'] = $result_format;
    if (!isset($p) || !$p->getErrors()) {
      $qt = $infos['query']['type'];
      if (!in_array($qt, array('select', 'ask', 'describe', 'construct', 'load', 'insert', 'delete', 'dump'))) {
        return $this->addError('Unsupported query type "'.$qt.'"');
      }
      $t1 = ARC2::mtime();
      $r = array('query_type' => $qt, 'result' => $this->runQuery($infos, $qt, $keep_bnode_ids, $q));
      $t2 = ARC2::mtime();
      $r['query_time'] = $t2 - $t1;
      /* query result */
      if ($result_format == 'raw') {
        return $r['result'];
      }
      if ($result_format == 'rows') {
        return $r['result']['rows'] ? $r['result']['rows'] : array();
      }
      if ($result_format == 'row') {
        return $r['result']['rows'] ? $r['result']['rows'][0] : array();
      }
      return $r;
    }
    return 0;
  }

  function runQuery($infos, $type, $keep_bnode_ids = 0, $q = '') {
    ARC2::inc('Store' . ucfirst($type) . 'QueryHandler');
    $cls = 'ARC2_Store' . ucfirst($type) . 'QueryHandler';
    $h =& new $cls($this->a, $this);
    $ticket = 1;
    $r = array();
    if ($q && ($type == 'select')) $ticket = $this->getQueueTicket($q);
    if ($ticket) {
      if ($type == 'load') {/* the LoadQH supports raw data as 2nd parameter */
        $r = $h->runQuery($infos, '', $keep_bnode_ids);
      }
      else {
        $r = $h->runQuery($infos, $keep_bnode_ids);
      }
    }
    if ($q && ($type == 'select')) $this->removeQueueTicket($ticket);
    $trigger_r = $this->processTriggers($type, $infos);
    return $r;
  }
  
  function processTriggers($type, $infos) {
    $r = array();
    $trigger_defs = $this->triggers;
    $this->triggers = array();
    if ($triggers = $this->v($type, array(), $trigger_defs)) {
      $r['trigger_results'] = array();
      $triggers = is_array($triggers) ? $triggers : array($triggers);
      $trigger_inc_path = $this->v('store_triggers_path', '', $this->a);
      foreach ($triggers as $trigger) {
        $trigger .= !preg_match('/Trigger$/', $trigger) ? 'Trigger' : '';
        if (ARC2::inc(ucfirst($trigger), $trigger_inc_path)) {
          $cls = 'ARC2_' . ucfirst($trigger);
          $config = array_merge($this->a, array('query_infos' => $infos));
          $trigger_obj = new $cls($config, $this);
          if (method_exists($trigger_obj, 'go')) {
            $r['trigger_results'][] = $trigger_obj->go();
          }
        }
      }
    }
    $this->triggers = $trigger_defs;
    return $r;
  }
  
  /*  */

  function getValueHash($val) {
    return abs(crc32($val));
  }

  function getTermID($val, $term = '') {
    $tbl = preg_match('/^(s|o)$/', $term) ? $term . '2val' : 'id2val';
    $con = $this->getDBCon();
    /* via hash */
    if (preg_match('/^(s2val|o2val)$/', $tbl) && $this->hasHashColumn($tbl)) {
      $sql = "SELECT id, val FROM " . $this->getTablePrefix() . $tbl . " WHERE val_hash = '" . $this->getValueHash($val) . "'";
      if (($rs = $this->queryDB($sql, $con)) && mysql_num_rows($rs)) {
        while ($row = mysql_fetch_array($rs)) {
          if ($row['val'] == $val) {
            return $row['id'];
          }
        }
      }
    }
    /* exact match */
    else {
      $sql = "SELECT id FROM " . $this->getTablePrefix() . $tbl . " WHERE val = BINARY '" . mysql_real_escape_string($val, $con) . "' LIMIT 1";
      if (($rs = $this->queryDB($sql, $con)) && mysql_num_rows($rs) && ($row = mysql_fetch_array($rs))) {
        return $row['id'];
      }
    }
    return 0;
  }

  function getIDValue($id, $term = '') {
    $tbl = preg_match('/^(s|o)$/', $term) ? $term . '2val' : 'id2val';
    $con = $this->getDBCon();
    $sql = "SELECT val FROM " . $this->getTablePrefix() . $tbl . " WHERE id = " . mysql_real_escape_string($id, $con) . " LIMIT 1";
    if (($rs = $this->queryDB($sql, $con)) && mysql_num_rows($rs) && ($row = mysql_fetch_array($rs))) {
      return $row['val'];
    }
    return 0;
  }

  /*  */
  
  function getLock($t_out = 10, $t_out_init = '') {
    if (!$t_out_init) $t_out_init = $t_out;
    $con = $this->getDBCon();
    $l_name = $this->a['db_name'] . '.' . $this->getTablePrefix() . '.write_lock';
    if ($rs = $this->queryDB('SELECT IS_FREE_LOCK("' . $l_name. '") AS success', $con)) {
      $row = mysql_fetch_array($rs);
      if (!$row['success']) {
        if ($t_out) {
          sleep(1);
          return $this->getLock($t_out - 1, $t_out_init);
        }
      }
      elseif ($rs = $this->queryDB('SELECT GET_LOCK("' . $l_name. '", ' . $t_out_init. ') AS success', $con)) {
        $row = mysql_fetch_array($rs);
        return $row['success'];
      }
    }
    return 0;   
  }
  
  function releaseLock() {
    $con = $this->getDBCon();
    return $this->queryDB('DO RELEASE_LOCK("' . $this->a['db_name'] . '.' . $this->getTablePrefix() . '.write_lock")', $con);
  }

  /*  */

  function processTables($level = 2, $operation = 'optimize') {/* 1: triple + g2t, 2: triple + *2val, 3: all tables */
    $con = $this->getDBCon();
    $pre = $this->getTablePrefix();
    $tbls = $this->getTables();
    $sql = '';
    foreach ($tbls as $tbl) {
      if (($level < 3) && preg_match('/(backup|setting)$/', $tbl)) continue;
      if (($level < 2) && preg_match('/(val)$/', $tbl)) continue;
      $sql .= $sql ? ', ' : strtoupper($operation) . ' TABLE ';
      $sql .= $pre . $tbl;
    }
    $this->queryDB($sql, $con);
    if ($err = mysql_error($con)) $this->addError($err . ' in ' . $sql);
  }

  function optimizeTables($level = 2) {
    return $this->processTables($level, 'optimize');
  }

  function checkTables($level = 2) {
    return $this->processTables($level, 'check');
  }

  function repairTables($level = 2) {
    return $this->processTables($level, 'repair');
  }

  /*  */

  function changeNamespaceURI($old_uri, $new_uri) {
    ARC2::inc('StoreHelper');
    $c = new ARC2_StoreHelper($this->a, $this);
    return $c->changeNamespaceURI($old_uri, $new_uri);
  }
  
  /*  */
  
  function getResourceLabel($res, $unnamed_label = 'An unnamed resource') {
    if (!isset($this->resource_labels)) $this->resource_labels = array();
    if (isset($this->resource_labels[$res])) return $this->resource_labels[$res];
    if (!preg_match('/^[a-z0-9\_]+\:[^\s]+$/si', $res)) return $res;/* literal */
    $ps = $this->getLabelProps();
    if ($this->getSetting('store_label_properties', '-') != md5(serialize($ps))) {
      $this->inferLabelProps($ps);
    }
    //$sub_q .= $sub_q ? ' || ' : '';
    //$sub_q .= 'REGEX(str(?p), "(last_name|name|fn|title|label)$", "i")';
    $q = 'SELECT ?label WHERE { <' . $res . '> ?p ?label . ?p a <http://semsol.org/ns/arc#LabelProperty> } LIMIT 3';
    $r = '';
    if ($rows = $this->query($q, 'rows')) {
      foreach ($rows as $row) {
        $r = strlen($row['label']) > strlen($r) ? $row['label'] : $r;
      }
    }
    if (!$r && preg_match('/^\_\:/', $res)) {
      return $unnamed_label;
    }
    $r = $r ? $r : preg_replace("/^(.*[\/\#])([^\/\#]+)$/", '\\2', str_replace('#self', '', $res));
    $r = str_replace('_', ' ', $r);
    $r = preg_replace('/([a-z])([A-Z])/e', '"\\1 " . strtolower("\\2")', $r);
    $this->resource_labels[$res] = $r;
    return $r;
  }
  
  function getLabelProps() {
    return array_merge(
      $this->v('rdf_label_properties' , array(), $this->a),
      array(
        'http://www.w3.org/2000/01/rdf-schema#label',
        'http://xmlns.com/foaf/0.1/name',
        'http://purl.org/dc/elements/1.1/title',
        'http://purl.org/rss/1.0/title',
        'http://www.w3.org/2004/02/skos/core#prefLabel',
        'http://xmlns.com/foaf/0.1/nick',
      )
    );
  }
  
  function inferLabelProps($ps) {
    $this->query('DELETE FROM <label-properties>');
    $sub_q = '';
    foreach ($ps as $p) {
      $sub_q .= ' <' . $p . '> a <http://semsol.org/ns/arc#LabelProperty> . ';
    }
    $this->query('INSERT INTO <label-properties> { ' . $sub_q. ' }');
    $this->setSetting('store_label_properties', md5(serialize($ps)));
  }
  
  /*  */

  function getResourcePredicates($res) {
    $r = array();
    if ($rows = $this->query('SELECT DISTINCT ?p WHERE { <' . $res . '> ?p ?o . }', 'rows')) {
      foreach ($rows as $row) {
        $r[$row['p']] = array();
      }
    }
    return $r;
  }
  
  function getDomains($p) {
    $r = array();
    foreach($this->query('SELECT DISTINCT ?type WHERE {?s <' . $p . '> ?o ; a ?type . }', 'rows') as $row) {
      $r[] = $row['type'];
    }
    return $r;
  }

  function getPredicateRange($p) {
    $row = $this->query('SELECT ?val WHERE {<' . $p . '> rdfs:range ?val . } LIMIT 1', 'row');
    return $row ? $row['val'] : '';
  }

  /*  */
  
  function logQuery($q) {
    $fp = @fopen("arc_query_log.txt", "a");
    @fwrite($fp, date('Y-m-d\TH:i:s\Z', time()) . ' : ' . $q . '' . "\n\n");
    @fclose($fp);
  }

  /*  */

}
