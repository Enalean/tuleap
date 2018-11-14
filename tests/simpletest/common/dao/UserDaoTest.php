<?php

class UserDaoTest extends TuleapTestCase {

     function testReplaceStringInList() {
         $da  = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
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
