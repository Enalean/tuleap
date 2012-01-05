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
    
    function testExtractReturnType() {
        $return = $this->GivenTheReturnTypeOfAddProject();
        $this->assertEqual($return, array('addProject' => 'xsd:int'));
    }
    
    function testExtractReturnTypeBoolean() {
        $gen = $this->GivenGenerator('returnBoolean');
        $this->assertEqual($gen->getReturnType(), array('returnBoolean' => 'xsd:boolean'));
    }
    
    private function assertDoesntContain($reference, $search) {
        $this->assertTrue(strpos($reference, $search) === false);
    }
    
    private function assertContains($reference, $search) {
        $this->assertTrue(strpos($reference, $search) !== false);
    }
    
    private function GivenTheCommentOfAddProject() {
        $gen = $this->GivenGenerator('addProject');
        return $gen->getComment();
    }
    
    private function GivenTheParametersOfAddProject() {
        $gen = $this->GivenGenerator('addProject');
        return $gen->getParameters();
    }
    
    private function GivenTheReturnTypeOfAddProject() {
        $gen = $this->GivenGenerator('addProject');
        return $gen->getReturnType();
    }
    
    private function GivenGenerator($methodName) {
        $class = new ReflectionClass('SOAP_WSDLGeneratorFixtures');
        return new SOAP_WSDLGenerator($class->getMethod($methodName));
    }
}

?>
