<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/soap/SOAP_WSDLGenerator.class.php';

require_once '_fixtures/SOAP_WSDLGeneratorFixtures.php';

class SOAP_WSDLGeneratorTest extends UnitTestCase {
    
    private function assertDoesntContain($reference, $search) {
        $this->assertTrue(strpos($reference, $search) === false);
    }
    
    private function assertContains($reference, $search) {
        $this->assertTrue(strpos($reference, $search) !== false);
    }
    
    private function GivenTheCommentOfAddProject() {
        $gen = new SOAP_WSDLGenerator('SOAP_WSDLGeneratorFixtures');
        return $gen->getComment('addProject');
    }
    
    private function GivenTheParametersOfAddProject() {
        $gen = new SOAP_WSDLGenerator('SOAP_WSDLGeneratorFixtures');
        return $gen->getParams('addProject');
    }
    
    function testExtractCommentShouldContainsComment() {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertContains($comment, 'Create a new project');
    }
    
    function testExtractCommentShouldNotContentCommentsDelimiters() {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertDoesntContain($comment, '/**');
        $this->assertDoesntContain($comment, '*/');
    }
    
    function testExtractCommentShouldNotContentDocblocks() {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertDoesntContain($comment, '@param');
        $this->assertDoesntContain($comment, '@return');
        $this->assertDoesntContain($comment, '@see');
    }
    
    function testExtractCommentShouldNotFinishByTonsOfSpaces() {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertFalse(preg_match('%[ ]+$%', $comment));
    }
    
    function testExtractParamsShouldListAllParameters() {
        $params = $this->GivenTheParametersOfAddProject();
        $this->assertEqual(count($params), 5);
    }
    
    function testExtractParamsShouldListParamsInOrder() {
        $params = $this->GivenTheParametersOfAddProject();
        
        $this->assertEqual($params, array(
            'requesterLogin' => 'xsd:string',
            'shortName'      => 'xsd:string',
            'realName'       => 'xsd:string',
            'privacy'        => 'xsd:string',
            'templateId'     => 'xsd:int'));
        
    }
    
}

?>
