<?php
/**
 * ARC2 RDF Store LOAD Query Handler
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-06-24
*/

ARC2::inc('StoreQueryHandler');

class ARC2_StoreLoadQueryHandler extends ARC2_StoreQueryHandler {

  function __construct($a = '', &$caller) {/* caller has to be a store */
    parent::__construct($a, $caller);
  }
  
  function ARC2_StoreLoadQueryHandler($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* db_con, store_log_inserts */
    parent::__init();
    $this->store =& $this->caller;
    $this->write_buffer_size = $this->v('store_write_buffer', 2500, $this->a);
    $this->split_threshold = $this->v('store_split_threshold', 0, $this->a);
    $this->has_pcre_unicode = @preg_match('/\pL/u', 'test');
    $this->strip_mb_comp_str = $this->v('store_strip_mb_comp_str', 0, $this->a);
  }

  /*  */
  
  function runQuery($infos, $data = '', $keep_bnode_ids = 0) {
    $url = $infos['query']['url'];
    $graph = $infos['query']['target_graph'];
    $this->target_graph = $graph ? $this->calcURI($graph) : $this->calcURI($url);
    $this->fixed_target_graph = $graph ? $this->target_graph : '';
    $this->keep_bnode_ids = $keep_bnode_ids;
    /* reader */
    ARC2::inc('Reader');
    $reader =& new ARC2_Reader($this->a, $this);
    $reader->activate($url, $data);
    /* format detection */
    $mappings = array(
      'rdfxml' => 'RDFXML', 
      'sparqlxml' => 'SPOG', 
      'turtle' => 'Turtle', 
      'ntriples' => 'Turtle', 
      'rss' => 'RSS',
      'atom' => 'Atom',
      'n3' => 'Turtle', 
      'html' => 'SemHTML',
      'sgajson' => 'SGAJSON',
      'cbjson' => 'CBJSON'
    );
    $format = $reader->getFormat();
    if (!$format || !isset($mappings[$format])) {
      return $this->addError('No loader available for "' .$url. '": ' . $format);
    }
    /* format loader */
    $suffix = 'Store' . $mappings[$format] . 'Loader';
    ARC2::inc($suffix);
    $cls = 'ARC2_' . $suffix;
    $loader =& new $cls($this->a, $this);
    $loader->setReader($reader);
    /* lock */
    if (!$this->store->getLock()) {
      $l_name = $this->a['db_name'] . '.' . $this->store->getTablePrefix() . '.write_lock';
      return $this->addError('Could not get lock in "runQuery" (' . $l_name . ')');
    }
    $this->has_lock = 1;
    /* logging */
    $this->t_count = 0;
    $this->t_start = ARC2::mtime();
    $this->log_inserts = $this->v('store_log_inserts', 0, $this->a);
    if ($this->log_inserts) {
      @unlink("arc_insert_log.txt");
      $this->inserts = array();
      $this->insert_times = array();
      $this->t_prev = $this->t_start;
      $this->t_count_prev = 0 ;
    }
    /* load and parse */
    $this->max_term_id = $this->getMaxTermID();
    $this->max_triple_id = $this->getMaxTripleID();
    $this->column_type = $this->store->getColumnType();
    //$this->createMergeTable();
    $this->term_ids = array();
    $this->triple_ids = array();
    $this->sql_buffers = array();
    $r = $loader->parse($url, $data);
    /* done */
    $this->checkSQLBuffers(1);
    if ($this->log_inserts) {
      $this->logInserts();
    }
    $this->store->releaseLock();
    //$this->dropMergeTable();
    if ((rand(1, 100) == 1)) $this->store->optimizeTables();
    $t2 = ARC2::mtime();
    $dur = round($t2 - $this->t_start, 4);
    $r = array(
      't_count' => $this->t_count,
      'load_time' => $dur,
    );
    if ($this->log_inserts) {
      $r['inserts'] = $this->inserts;
      $r['insert_times'] = $this->insert_times;
    }
    return $r;
  }
  
  /*  */

  function addT($s, $p, $o, $s_type, $o_type, $o_dt = '', $o_lang = '') {
    if (!$this->has_lock) return 0;
    $type_ids = array ('uri' => '0', 'bnode' => '1' , 'literal' => '2');
    $g = $this->getTermID($this->target_graph, '0', 'id');
    $s = (($s_type == 'bnode') && !$this->keep_bnode_ids) ? '_:b' . abs(crc32($g . $s)) . '_' . (strlen($s) > 12 ? substr(substr($s, 2) , -10) : substr($s, 2)) : $s;
    $o = (($o_type == 'bnode') && !$this->keep_bnode_ids) ? '_:b' . abs(crc32($g . $o)) . '_' . (strlen($o) > 12 ? substr(substr($o, 2), -10) : substr($o, 2)) : $o;
    /* triple */
    $t = array(
      's' => $this->getTermID($s, $type_ids[$s_type], 's'),
      'p' => $this->getTermID($p, '0', 'id'),
      'o' => $this->getTermID($o, $type_ids[$o_type], 'o'),
      'o_lang_dt' => $this->getTermID($o_dt . $o_lang, $o_dt ? '0' : '2', 'id'),
      'o_comp' => $this->getOComp($o),
      's_type' => $type_ids[$s_type], 
      'o_type' => $type_ids[$o_type],
    );
    $t['t'] = $this->getTripleID($t);
    if (is_array($t['t'])) {/* t exists already */
      $t['t'] = $t['t'][0];
    }
    else {
      $this->bufferTripleSQL($t);
    }
    /* g2t */
    $g2t = array('g' => $g, 't' => $t['t']);
    $this->bufferGraphSQL($g2t);
    $this->t_count++;
    /* check buffers */
    if (($this->t_count % $this->write_buffer_size) == 0) {
      $force_write = 1;
      $reset_buffers = (($this->t_count % ($this->write_buffer_size * 2)) == 0);
      $refresh_lock = (($this->t_count % 25000) == 0);
      $split_tables = (($this->t_count % ($this->write_buffer_size * 10)) == 0);
      if ($this->log_inserts) $this->logInserts();
      $this->checkSQLBuffers($force_write, $reset_buffers, $refresh_lock, $split_tables);
    }
  }

  /*  */
  
  function getMaxTermID() {
    $con = $this->store->getDBCon();
    $sql = '';
    foreach (array('id2val', 's2val', 'o2val') as $tbl) {
      $sql .= $sql ? ' UNION ' : '';
      $sql .= "(SELECT MAX(id) as `id` FROM " . $this->store->getTablePrefix() . $tbl . ')';
    }
    $r = 0;
    if (($rs = $this->queryDB($sql, $con)) && mysql_num_rows($rs)) {
      while ($row = mysql_fetch_array($rs)) {
        $r = ($r < $row['id']) ? $row['id'] : $r;
      }
    }
    return $r + 1;
  }
  
  function getMaxTripleID() {
    $con = $this->store->getDBCon();
    $sql = "SELECT MAX(t) AS `id` FROM " . $this->store->getTablePrefix() . "triple";
    if (($rs = $this->queryDB($sql, $con)) && mysql_num_rows($rs) && ($row = mysql_fetch_array($rs))) {
      return $row['id'] + 1;
    }
    return 1;
  }

  function getTermID($val, $type_id, $tbl) {
    $con = $this->store->getDBCon();
    /* buffered */
    if (isset($this->term_ids[$val])) {
      if (!isset($this->term_ids[$val][$tbl])) {
        foreach (array('id', 's', 'o') as $other_tbl) {
          if (isset($this->term_ids[$val][$other_tbl])) {
            $this->term_ids[$val][$tbl] = $this->term_ids[$val][$other_tbl];
            $this->bufferIDSQL($tbl, $this->term_ids[$val][$tbl], $val, $type_id);
            break;
          }
        }
      }
      return $this->term_ids[$val][$tbl];
    }
    /* db */
    $tbl_prefix = $this->store->getTablePrefix();
    $sub_tbls = ($tbl == 'id') ? array('id2val', 's2val', 'o2val') : ($tbl == 's' ? array('s2val', 'id2val', 'o2val') : array('o2val', 'id2val', 's2val'));
    foreach ($sub_tbls as $sub_tbl) {
      $id = 0;
      //$sql = "SELECT id AS `id`, '" . $sub_tbl . "' AS `tbl` FROM " . $tbl_prefix . $sub_tbl . " WHERE val = BINARY '" . mysql_real_escape_string($val, $con) . "'";
      /* via hash */
      if (preg_match('/^(s2val|o2val)$/', $sub_tbl) && $this->hasHashColumn($sub_tbl)) {
        $sql = "SELECT id AS `id`, val AS `val` FROM " . $tbl_prefix . $sub_tbl . " WHERE val_hash = BINARY '" . $this->getValueHash($val) . "'";
        if (($rs = $this->queryDB($sql, $con)) && mysql_num_rows($rs)) {
          while ($row = mysql_fetch_array($rs)) {
            if ($row['val'] == $val) {
              $id = $row['id'];
              break;
            }
          }
        }
      }
      else {
        $sql = "SELECT id AS `id` FROM " . $tbl_prefix . $sub_tbl . " WHERE val = BINARY '" . mysql_real_escape_string($val, $con) . "'";
        if (($rs = $this->queryDB($sql . ' LIMIT 1', $con)) && mysql_num_rows($rs)) {
          $row = mysql_fetch_array($rs);
          $id = $row['id'];
        }
      }
      if ($id) {
        $this->term_ids[$val] = array($tbl => $id);
        if ($sub_tbl != $tbl . '2val') {
          $this->bufferIDSQL($tbl, $id, $val, $type_id);
        }
        break;
      }
    }
    /* new */
    if (!isset($this->term_ids[$val])) {
      $this->term_ids[$val] = array($tbl => $this->max_term_id);
      $this->bufferIDSQL($tbl, $this->max_term_id, $val, $type_id);
      $this->max_term_id++;
      /* upgrade tables ? */
      if (($this->column_type == 'mediumint') && ($this->max_term_id >= 16750000)) {
        $this->store->extendColumns();
        $this->column_type = 'int';
      }
    }
    return $this->term_ids[$val][$tbl];
  }

  function getTripleID($t) {
    $con = $this->store->getDBCon();
    $val = serialize($t);
    /* buffered */
    if (isset($this->triple_ids[$val])) {
      return array($this->triple_ids[$val]);/* hack for "don't insert this triple" */
    }
    /* db */
    $sql = "SELECT t FROM " . $this->store->getTablePrefix() . "triple WHERE 
      s = " . $t['s'] . " AND p = " . $t['p'] . " AND o = " . $t['o'] . " AND o_lang_dt = " . $t['o_lang_dt'] . " AND s_type = " . $t['s_type'] . " AND o_type = " . $t['o_type'] . "
      LIMIT 1
    ";
    if (($rs = $this->queryDB($sql, $con)) && mysql_num_rows($rs) && ($row = mysql_fetch_array($rs))) {
      $this->triple_ids[$val] = $row['t'];/* hack for "don't insert this triple" */
      return array($row['t']);/* hack for "don't insert this triple" */
    }
    /* new */
    else {
      $this->triple_ids[$val] = $this->max_triple_id;
      $this->max_triple_id++;
      /* split tables ? */
      if ($this->split_threshold && !($this->max_triple_id % $this->split_threshold)) {
        $this->store->splitTables();
        $this->dropMergeTable();
        $this->createMergeTable();
      }
      return $this->triple_ids[$val];
    }
  }
  
  function getOComp($val) {
    /* try date (e.g. 21 August 2007) */
    if (preg_match('/^[0-9]{1,2}\s+[a-z]+\s+[0-9]{4}/i', $val) && ($uts = strtotime($val)) && ($uts !== -1)) {
      return date("Y-m-d\TH:i:s", $uts);
    }
    /* xsd date (e.g. 2009-05-28T18:03:38+09:00 2009-05-28T18:03:38GMT) */
    if (preg_match('/^([0-9]{4}\-[0-9]{2}\-[0-9]{2}\T)([0-9\:]+)?([0-9\+\-\:\Z]+)?(\s*[a-z]{2,3})?$/si', $val, $m)) {
      /* yyyy-mm-dd */
      $val = $m[1];
      /* hh:ss */
      if ($m[2]) {
        $val .= $m[2];
        /* timezone offset */
        if (isset($m[3]) && ($m[3] != 'Z')) {
          $uts = strtotime(str_replace('T', ' ', $val));
          if (preg_match('/([\+\-])([0-9]{2})\:?([0-9]{2})$/', $m[3], $sub_m)) {
            $diff_mins = (3600 * ltrim($sub_m[2], '0')) + ltrim($sub_m[3], '0');
            $uts = ($sub_m[1] == '-') ? $uts + $diff_mins : $uts - $diff_mins;
            $val = date('Y-m-d\TH:i:s\Z', $uts);
          }
        }
        else {
          $val .= 'Z';
        }
      }
      return $val;
    }
    /* fallback & backup w/o UTC calculation, to be removed in later revision */
    if (preg_match('/^[0-9]{4}[0-9\-\:\T\Z\+]+([a-z]{2,3})?$/i', $val)) {
      return $val;
    }
    if (is_numeric($val)) {
      $val = sprintf("%f", $val);
      if (preg_match("/([\-\+])([0-9]*)\.([0-9]*)/", $val, $m)) {
        return $m[1] . sprintf("%018s", $m[2]) . "." . sprintf("%-015s", $m[3]);
      }
      if (preg_match("/([0-9]*)\.([0-9]*)/", $val, $m)) {
        return "+" . sprintf("%018s", $m[1]) . "." . sprintf("%-015s", $m[2]);
      }
      return $val;
    }
    /* any other string: remove tags, linebreaks etc., but keep MB-chars  */
    //$val = substr(trim(preg_replace('/[\W\s]+/is', '-', strip_tags($val))), 0, 35);
    $re = $this->has_pcre_unicode ? '/[\PL\s]+/isu' : '/[\s\'\"\´\`]+/is';
    $val = trim(preg_replace($re, '-', strip_tags($val)));
    if (strlen($val) > 35) {
      $fnc = function_exists("mb_substr") ? 'mb_substr' : 'substr';
      $val = $fnc($val, 0, 17) . '-' . $fnc($val, -17);
    }
    if ($this->strip_mb_comp_str) {
      $val = urldecode(preg_replace('/\%[0-9A-F]{2}/', '', urlencode($val)));
    }
    return $this->toUTF8($val);
  }

  /*  */
  
  function bufferTripleSQL($t) {
    $con = $this->store->getDBCon();
    $tbl = 'triple';
    $sql = ", ";
    if (!isset($this->sql_buffers[$tbl])) {
      $this->sql_buffers[$tbl] = "INSERT IGNORE INTO " . $this->store->getTablePrefix() . $tbl . " (t, s, p, o, o_lang_dt, o_comp, s_type, o_type) VALUES";
      $sql = " ";
    }
    $this->sql_buffers[$tbl] .= $sql . "(" . $t['t'] . ", " . $t['s'] . ", " . $t['p'] . ", " . $t['o'] . ", " . $t['o_lang_dt'] . ", '" . mysql_real_escape_string($t['o_comp'], $con) . "', " . $t['s_type'] . ", " . $t['o_type'] . ")";
  }
  
  function bufferGraphSQL($g2t) {
    $tbl = 'g2t';
    $sql = ", ";
    if (!isset($this->sql_buffers[$tbl])) {
      $this->sql_buffers[$tbl] = "INSERT IGNORE INTO " . $this->store->getTablePrefix() . $tbl . " (g, t) VALUES";
      $sql = " ";
    }
    $this->sql_buffers[$tbl] .= $sql . "(" . $g2t['g'] . ", " . $g2t['t'] . ")";
  }
  
  function bufferIDSQL($tbl, $id, $val, $val_type) {
    $con = $this->store->getDBCon();
    $tbl = $tbl . '2val';
    if ($tbl == 'id2val') {
      $cols = "id, val, val_type";
      $vals = "(" . $id . ", '" . mysql_real_escape_string($val, $con) . "', " . $val_type . ")";
    }
    elseif (preg_match('/^(s2val|o2val)$/', $tbl) && $this->hasHashColumn($tbl)) {
      $cols = "id, val_hash, val";
      $vals = "(" . $id . ", '" . $this->getValueHash($val). "', '" . mysql_real_escape_string($val, $con) . "')";
    }
    else {
      $cols = "id, val";
      $vals = "(" . $id . ", '" . mysql_real_escape_string($val, $con) . "')";
    }
    if (!isset($this->sql_buffers[$tbl])) {
      $this->sql_buffers[$tbl] = '';
      $sql = "INSERT IGNORE INTO " . $this->store->getTablePrefix() . $tbl . "(" . $cols . ") VALUES ";
    }
    else {
      $sql = ", ";
    }
    $sql .= $vals;
    $this->sql_buffers[$tbl] .= $sql;
  }
  
  /*  */

  function checkSQLBuffers($force_write = 0, $reset_id_buffers = 0, $refresh_lock = 0, $split_tables = 0) {
    $con = $this->store->getDBCon();
    if (!$this->keep_time_limit) @set_time_limit($this->v('time_limit', 60, $this->a));
    foreach (array('triple', 'g2t', 'id2val', 's2val', 'o2val') as $tbl) {
      $buffer_size = isset($this->sql_buffers[$tbl]) ? 1 : 0;
      if ($buffer_size && $force_write) {
        $t1 = ARC2::mtime();
        $this->queryDB($this->sql_buffers[$tbl], $con);
        /* table error */
        if ($er = mysql_error($con)) {
          $this->autoRepairTable($er, $con, $this->sql_buffers[$tbl]);
        }
        unset($this->sql_buffers[$tbl]);
        if ($this->log_inserts) {
          $t2 = ARC2::mtime();
          $this->inserts[$tbl] = $this->v($tbl, 0, $this->inserts) + max(0, mysql_affected_rows($con));
          $dur = round($t2 - $t1, 4);
          $this->insert_times[$tbl] = isset($this->insert_times[$tbl]) ? $this->insert_times[$tbl] : array('min' => $dur, 'max' => $dur, 'sum' => $dur);
          $this->insert_times[$tbl] = array('min' => min($dur, $this->insert_times[$tbl]['min']), 'max' => max($dur, $this->insert_times[$tbl]['max']), 'sum' => $dur + $this->insert_times[$tbl]['sum']);
        }
        /* reset term id buffers */
        if ($reset_id_buffers) {
          $this->term_ids = array();
          $this->triple_ids = array();
        }
        /* refresh lock */
        if ($refresh_lock) {
          $this->store->releaseLock();
          $this->has_lock = 0;
          sleep(1);
          if (!$this->store->getLock(5)) return $this->addError('Could not re-obtain lock in "checkSQLBuffers"');
          $this->has_lock = 1;
        }
      }
    }
    return 1;
  }

  function autoRepairTable($er, $con, $sql = '') {
    $this->addError('MySQL error: ' . $er . ' (' . $sql . ')');
    if (preg_match('/Table \'[^\']+\/([a-z0-9\_\-]+)\' .*(crashed|repair)/i', $er, $m)) {
      $rs = $this->queryDB('REPAIR TABLE ' . rawurlencode($m[1]), $con);
      $msg = $rs ? mysql_fetch_array($rs) : array();
      if ($this->v('Msg_type', 'error', $msg) == 'error') {
        /* auto-reset */
        if ($this->v('store_reset_on_table_crash', 0, $this->a)) {
          $this->store->drop();
          $this->store->setUp();
        }
        else {
          $er = $this->v('Msg_text', 'unknown error', $msg);
          $this->addError('Auto-repair failed on ' . rawurlencode($m[1]) . ': ' . $er);
        }
        //die("Fatal errors: \n" . print_r($this->getErrors(), 1));
      }
    }
  }

  /* speed log */
  
  function logInserts() {
    $t_start = $this->t_start;
    $t_prev = $this->t_prev;
    $t_now = ARC2::mtime();
    $tc_prev = $this->t_count_prev;
    $tc_now = $this->t_count;
    $tc_diff = $tc_now - $tc_prev;
    
    $dur_full = $t_now - $t_start;
    $dur_diff = $t_now - $t_prev;

    $speed_full = round($tc_now / $dur_full);
    $speed_now = round($tc_diff / $dur_diff);

    $r = $tc_diff . ' in ' . round($dur_diff, 5) . ' = ' . $speed_now . ' t/s  (' .$tc_now. ' in ' . round($dur_full, 5). ' = ' . $speed_full . ' t/s )'; 
    $fp = @fopen("arc_insert_log.txt", "a");
    @fwrite($fp, $r . "\r\n");
    @fclose($fp);
    
    $this->t_prev = $t_now;
    $this->t_count_prev = $tc_now;
  }

}
