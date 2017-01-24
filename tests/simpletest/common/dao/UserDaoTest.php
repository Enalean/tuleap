<?php
require_once('common/dao/UserDao.class.php');

require_once('common/dao/include/DataAccess.class.php');
Mock::generate('DataAccess');

class UserDaoTest extends TuleapTestCase {

     function testReplaceStringInList() {
         $da  = new MockDataAccess($this);
         $dao = new UserDao($da);

         $this->assertEqual($dao->replaceStringInList('foo', 'foo', 'tutu'), 'tutu');
         $this->assertEqual($dao->replaceStringInList('   foo', 'foo', 'tutu'), '   tutu');
         $this->assertEqual($dao->replaceStringInList('foo   ', 'foo', 'tutu'), 'tutu   ');
         
         $this->assertEqual($dao->replaceStringInList('foo,bar', 'foo', 'tutu'), 'tutu,bar');
         $this->assertEqual($dao->replaceStringInList('foo, bar', 'foo', 'tutu'), 'tutu, bar');
         $this->assertEqual($dao->replaceStringInList('foo ,bar', 'foo', 'tutu'), 'tutu ,bar');

         $this->assertEqual($dao->replaceStringInList('bar,foo,toto', 'foo', 'tutu'), 'bar,tutu,toto');
         $this->assertEqual($dao->replaceStringInList('bar  ,  foo  ,  toto', 'foo', 'tutu'), 'bar  ,  tutu  ,  toto');
         
         $this->assertEqual($dao->replaceStringInList('bar,wwwfoo,toto', 'foo', 'tutu'), 'bar,wwwfoo,toto');
         $this->assertEqual($dao->replaceStringInList('bar,  wwwfoo,toto ', 'foo', 'tutu'), 'bar,  wwwfoo,toto ');
         
         $this->assertEqual($dao->replaceStringInList('bar,foowww,foo', 'foo', 'tutu'), 'bar,foowww,tutu');
         $this->assertEqual($dao->replaceStringInList('bar, foowww, foo', 'foo', 'tutu'), 'bar, foowww, tutu');
         
         $this->assertEqual($dao->replaceStringInList('foo,foo', 'foo', 'tutu'), 'tutu,tutu');
         $this->assertEqual($dao->replaceStringInList('foo,bar,foo', 'foo', 'tutu'), 'tutu,bar,tutu');
     }
}
?>
