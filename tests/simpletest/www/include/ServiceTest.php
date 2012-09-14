<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'www/include/service.php';

class ServiceTest extends TuleapTestCase {
    
    public function setUp() {
        $this->template        = array(
            'name' => 'template-name',
            'id'   => 120
        );
        $this->project_manager = mock('ProjectManager');
        $this->group_id        = 101;
        $this->project         = mock('Project');
        stub($this->project)->getUnixName()->returns('h1tst');
        
        stub($this->project_manager)->getProject($this->group_id)->returns($this->project);        
    }
    
    private function assertLinkEquals($link, $expected) {
        $result = service_replace_template_name_in_link($link, $this->template, $this->project_manager, $this->group_id);
        $this->assertEqual($expected, $result);
    }
    
    public function itReplacesNameIfLinkIsDashboard() {
        $link     = '/projects/template-name/';
        $expected = '/projects/h1tst/';
        $this->assertLinkEquals($link, $expected);
    }
    
    public function itReplacesNameIfLinkContainesAmpersand() {
        $link     = 'test=template-name&group=template-name';
        $expected = 'test=template-name&group=h1tst';
        $this->assertLinkEquals($link, $expected);
    }
    
    public function itReplacesGroupId() {
        $link     = '/www/?group_id=120';
        $expected = '/www/?group_id=101';
        $this->assertLinkEquals($link, $expected);
    }
    
    public function itDoesntReplaceGroupIdIfNoMatch() {
        $link     = '/www/?group_id=1204'; //template id is 120
        $expected = '/www/?group_id=1204';
        $this->assertLinkEquals($link, $expected);
    }
    
    public function itReplacesWebroot() {
        $link     = '/www/template-name/';
        $expected = '/www/h1tst/';
        $this->assertLinkEquals($link, $expected);
    }
    
    public function itReplacesWhenUsedAsQueryParameter() {
        $link     = 'group=template-name';
        $expected = 'group=h1tst';
        $this->assertLinkEquals($link, $expected);
    }
    
    public function itDoesntReplaceWhenNameIsPartOfAPluginName() {
        $this->template['name'] = 'agile';
        $link     = '/plugins/agiledashboard/';
        $expected = '/plugins/agiledashboard/';
        $this->assertLinkEquals($link, $expected);
    }
    
}
?>
