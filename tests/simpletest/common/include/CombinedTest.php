<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('common/include/Combined.class.php');
Mock::generatePartial(
    'Combined', 
    'CombinedTestVersion', 
    array('getCombinedScripts', 
          'getDestinationDir', 
          'getSourceDir',
          'onTheFly',
    )
);

class WhiteBox_Combined extends Combined {
    public function getSourceDir_exposed($params) {
        return parent::getSourceDir($params);
    }
}
class CombinedTest extends UnitTestCase {
    
    
    public function setUp() {
        $class = new ReflectionClass('Combined');
        $this->_scripts = array(
            dirname(__FILE__). '/_fixtures/scripts/prototype.js' => '',
            dirname(__FILE__). '/_fixtures/plugins/docman/docman.js' => '',
            dirname(__FILE__). '/_fixtures/scripts/calendar.js' => '',
            dirname(__FILE__). '/_fixtures/in_the_future/in_the_future.js' => '',
            $class->getFileName() => '',
        );
        
        foreach($this->_scripts as $file => $nop) {
            $this->_scripts[$file] = filemtime($file);
            touch($file, $_SERVER['REQUEST_TIME'] - 2 * 3600);
        }
        
        file_put_contents(dirname(__FILE__). '/_fixtures/scripts/combined/codendi-1.js', "//Prototype file\n//Docman file\n");
    }
    
    public function tearDown() {
        foreach($this->_scripts as $file => $original_filemtime) {
            touch($file, $original_filemtime);
        }
        foreach(glob(dirname(__FILE__). '/_fixtures/scripts/combined/*.js') as $file) {
            unlink($file);
        }
    }
    
    public function testGetScripts() {
        $c = new CombinedTestVersion($this);
        //in this test, combined script is made of prototype+docman
        $c->setReturnValue('getCombinedScripts', array('/scripts/prototype.js', '/plugins/docman/docman.js'));
        
        $c->setReturnValue('getSourceDir', dirname(__FILE__). '/_fixtures/scripts/combined/codendi-', array('/scripts/combined/codendi-'));
        $c->setReturnValue('onTheFly', false);
        
        $expected_combined     = '<script type="text/javascript" src="/scripts/combined/codendi-1.js"></script>';
        $expected_not_combined = '<script type="text/javascript" src="/scripts/calendar.js"></script>';
        
        $this->assertEqual($c->getScripts('/scripts/prototype.js'),     $expected_combined);
        $this->assertEqual($c->getScripts('/plugins/docman/docman.js'), $expected_combined);
        $this->assertEqual($c->getScripts('/scripts/calendar.js'),      $expected_not_combined);
        
        $this->assertEqual($c->getScripts(array('/scripts/prototype.js', 
                                                '/plugins/docman/docman.js')), $expected_combined);
        $this->assertEqual($c->getScripts(array('/scripts/prototype.js', 
                                                '/scripts/calendar.js')), $expected_combined . $expected_not_combined);
        $this->assertEqual($c->getScripts(array('/scripts/prototype.js', 
                                                '/plugins/docman/docman.js', 
                                                '/scripts/calendar.js')), $expected_combined . $expected_not_combined);
        $this->assertEqual($c->getScripts(array('/scripts/prototype.js', 
                                                '/scripts/calendar.js',
                                                '/plugins/docman/docman.js')), $expected_combined . $expected_not_combined);
    }
    
    public function testGenerate() {
        
        $c = new CombinedTestVersion($this);
        //in this test, combined script is made of prototype+docman
        $c->setReturnValue('getCombinedScripts', array('/scripts/prototype.js', '/plugins/docman/docman.js'));
        $c->setReturnValue('getSourceDir',       dirname(__FILE__). '/_fixtures/scripts/prototype.js', array('/scripts/prototype.js'));
        $c->setReturnValue('getSourceDir',       dirname(__FILE__). '/_fixtures/in_the_future/in_the_future.js', array('/in_the_future/in_the_future.js'));
        $c->setReturnValue('getSourceDir',       dirname(__FILE__). '/_fixtures/plugins/docman/docman.js', array('/plugins/docman/docman.js'));
        $c->setReturnValue('getDestinationDir',  dirname(__FILE__). '/_fixtures/scripts/combined/');
        $c->setReturnValue('onTheFly', false);
        
        $c->generate();
        
        $expected_combined = dirname(__FILE__). '/_fixtures/scripts/combined/codendi-'. $_SERVER['REQUEST_TIME'] .'.js';
        $this->assertTrue(is_file($expected_combined));
        $generated_content = file_get_contents($expected_combined);
        $this->assertPattern('/Prototype/',  $generated_content);
        $this->assertPattern('/Docman/',     $generated_content);
        $this->assertNoPattern('/Calendar/', $generated_content);
        $this->assertNoPattern('/in the future/', $generated_content);
    }
    
    public function testGetSourceDir() {
        $c = new WhiteBox_Combined();
        $this->assertEqual($c->getSourceDir_exposed('/plugins/docman/docman.js'), $GLOBALS['sys_pluginsroot']. 'docman/www/docman.js');
        $this->assertEqual($c->getSourceDir_exposed('/scripts/prototype.js'), $GLOBALS['codendi_dir']. '/src/www/scripts/prototype.js');
    }
    
    public function testAutoGenerate() {
        $c = new CombinedTestVersion($this);
        $c->setReturnValue('onTheFly', true);
        
        //in this test, combined script is made of prototype+docman
        $c->setReturnValue('getCombinedScripts', array('/scripts/prototype.js', '/plugins/docman/docman.js', '/in_the_future/in_the_future.js'));
        $c->setReturnValue('getSourceDir',       dirname(__FILE__). '/_fixtures/scripts/prototype.js', array('/scripts/prototype.js'));
        $c->setReturnValue('getSourceDir',       dirname(__FILE__). '/_fixtures/in_the_future/in_the_future.js', array('/in_the_future/in_the_future.js'));
        $c->setReturnValue('getSourceDir',       dirname(__FILE__). '/_fixtures/plugins/docman/docman.js', array('/plugins/docman/docman.js'));
        $c->setReturnValue('getSourceDir',       dirname(__FILE__). '/_fixtures/scripts/combined/codendi-', array('/scripts/combined/codendi-'));
        $c->setReturnValue('getSourceDir',       dirname(__FILE__). '/_fixtures/scripts/combined/codendi-1.js', array('/scripts/combined/codendi-1.js'));
        $c->setReturnValue('getDestinationDir',  dirname(__FILE__). '/_fixtures/scripts/combined/');
        
        $c->autoGenerate();
        
        $expected_combined = dirname(__FILE__). '/_fixtures/scripts/combined/codendi-1.js';
        $this->assertTrue(is_file($expected_combined));
        $generated_content = file_get_contents($expected_combined);
        $this->assertPattern('/Prototype/',  $generated_content);
        $this->assertPattern('/Docman/',     $generated_content);
        $this->assertNoPattern('/Calendar/', $generated_content);
        $this->assertNoPattern('/in the future/', $generated_content); //even if the script is part of combined scripts
                                                                       //we don't expect its content since it is only
                                                                       //here to test updates (see below)
        
        //Now the site admin has just updated the server and in_the_future.js is like an updated script
        touch(dirname(__FILE__). '/_fixtures/in_the_future/in_the_future.js', $_SERVER['REQUEST_TIME'] + 2 * 3600);
        $c->autoGenerate();
        
        $expected_combined = dirname(__FILE__). '/_fixtures/scripts/combined/codendi-'. $_SERVER['REQUEST_TIME'] .'.js';
        $this->assertTrue(is_file($expected_combined));
        $generated_content = file_get_contents($expected_combined);
        $this->assertPattern('/Prototype/',  $generated_content);
        $this->assertPattern('/Docman/',     $generated_content);
        $this->assertNoPattern('/Calendar/', $generated_content);
        $this->assertPattern('/in the future/', $generated_content); //Now we expect that the file has been updated
    }
}
?>
