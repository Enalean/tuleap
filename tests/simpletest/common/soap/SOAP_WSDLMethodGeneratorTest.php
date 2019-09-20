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

require_once '_fixtures/SOAP_WSDLGeneratorFixtures.php';

class SOAP_WSDLMethodGeneratorTest extends TuleapTestCase
{

    function tearDown()
    {
        EventManager::clearInstance();
    }

    function testExtractCommentShouldContainsComment()
    {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertContains($comment, 'Create a new project');
    }

    function testExtractCommentShouldContainsParams()
    {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertContains($comment, '@param');
    }

    function testExtractCommentShouldNotContainsCommentsDelimiters()
    {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertDoesntContain($comment, '/**');
        $this->assertDoesntContain($comment, '*/');
    }

    function testExtractCommentShouldNotContainsDocblocks()
    {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertDoesntContain($comment, '@return');
        $this->assertDoesntContain($comment, '@see');
        $this->assertDoesntContain($comment, '@todo');
    }

    function testExtractCommentShouldNotFinishByTonsOfSpaces()
    {
        $comment = $this->GivenTheCommentOfAddProject();
        $this->assertFalse(preg_match('%[ ]+$%', $comment));
    }

    function testExtractParamsShouldListAllParameters()
    {
        $params = $this->GivenTheParametersOfAddProject();
        $this->assertEqual(count($params), 5);
    }

    function testExtractParamsShouldListParamsInOrder()
    {
        $params = $this->GivenTheParametersOfAddProject();

        $this->assertEqual($params, array(
            'requesterLogin' => 'xsd:string',
            'shortName'      => 'xsd:string',
            'realName'       => 'xsd:string',
            'privacy'        => 'xsd:string',
            'templateId'     => 'xsd:int'));
    }

    function testExtractReturnType()
    {
        $return = $this->GivenTheReturnTypeOfAddProject();
        $this->assertEqual($return, array('addProject' => 'xsd:int'));
    }

    function testExtractReturnTypeBoolean()
    {
        $gen = $this->GivenGenerator('returnBoolean');
        $this->assertEqual($gen->getReturnType(), array('returnBoolean' => 'xsd:boolean'));
    }

    function itExtractReturnTypeArrayOfString()
    {
        $gen = $this->GivenGenerator('returnArrayOfString');
        $this->assertEqual($gen->getReturnType(), array('returnArrayOfString' => 'tns:ArrayOfstring'));
    }

    function itThrowAnExceptionOnUnknownTypes()
    {
        $this->expectException('Exception');
        $this->GivenGenerator('returnUnknownType');
    }

    function itAsksToPluginsForUnkownTypes()
    {
        $plugin = new SOAP_WSDLMethodGeneratorTest_FakePlugin();
        EventManager::instance()->addListener(Event::WSDL_DOC2SOAP_TYPES, $plugin, 'wsdl_doc2soap_types', false);

        $gen = $this->GivenGenerator('returnArrayOfPluginTypes');
        $this->assertEqual($gen->getReturnType(), array('returnArrayOfPluginTypes' => 'tns:ArrayOfStats'));
    }

    private function assertDoesntContain($reference, $search)
    {
        $this->assertTrue(strpos($reference, $search) === false);
    }

    private function assertContains($reference, $search)
    {
        $this->assertTrue(strpos($reference, $search) !== false);
    }

    private function GivenTheCommentOfAddProject()
    {
        $gen = $this->GivenGenerator('addProject');
        return $gen->getComment();
    }

    private function GivenTheParametersOfAddProject()
    {
        $gen = $this->GivenGenerator('addProject');
        return $gen->getParameters();
    }

    private function GivenTheReturnTypeOfAddProject()
    {
        $gen = $this->GivenGenerator('addProject');
        return $gen->getReturnType();
    }

    private function GivenGenerator($methodName)
    {
        $class = new ReflectionClass('SOAP_WSDLGeneratorFixtures');
        return new SOAP_WSDLMethodGenerator($class->getMethod($methodName));
    }
}

class SOAP_WSDLMethodGeneratorTest_FakePlugin
{
    function wsdl_doc2soap_types($params)
    {
        $params['doc2soap_types'] = array_merge($params['doc2soap_types'], array(
            'arrayofplugintypes' => 'tns:ArrayOfStats'
        ));
    }
}
