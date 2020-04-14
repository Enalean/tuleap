<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class SOAP_WSDLMethodGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        parent::tearDown();
    }

    public function testExtractCommentShouldContainsComment(): void
    {
        $comment = $this->givenTheCommentOfAddProject();
        $this->assertStringContainsString('Create a new project', $comment);
    }

    public function testExtractCommentShouldContainsParams(): void
    {
        $comment = $this->givenTheCommentOfAddProject();
        $this->assertStringContainsString('@param', $comment);
    }

    public function testExtractCommentShouldNotContainsCommentsDelimiters(): void
    {
        $comment = $this->givenTheCommentOfAddProject();
        $this->assertStringNotContainsString('/**', $comment);
        $this->assertStringNotContainsString('*/', $comment);
    }

    public function testExtractCommentShouldNotContainsDocblocks(): void
    {
        $comment = $this->givenTheCommentOfAddProject();
        $this->assertStringNotContainsString('@return', $comment);
        $this->assertStringNotContainsString('@see', $comment);
        $this->assertStringNotContainsString('@todo', $comment);
    }

    public function testExtractCommentShouldNotFinishByTonsOfSpaces(): void
    {
        $comment = $this->givenTheCommentOfAddProject();
        $this->assertDoesNotMatchRegularExpression('%[ ]+$%', $comment);
    }

    public function testExtractParamsShouldListAllParameters(): void
    {
        $params = $this->givenTheParametersOfAddProject();
        $this->assertCount(5, $params);
    }

    public function testExtractParamsShouldListParamsInOrder(): void
    {
        $params = $this->givenTheParametersOfAddProject();

        $this->assertEquals(
            [
                'requesterLogin' => 'xsd:string',
                'shortName'      => 'xsd:string',
                'realName'       => 'xsd:string',
                'privacy'        => 'xsd:string',
                'templateId'     => 'xsd:int'
            ],
            $params
        );
    }

    public function testExtractReturnType(): void
    {
        $return = $this->givenTheReturnTypeOfAddProject();
        $this->assertEquals(array('addProject' => 'xsd:int'), $return);
    }

    public function testExtractReturnTypeBoolean(): void
    {
        $gen = $this->givenGenerator('returnBoolean');
        $this->assertEquals(array('returnBoolean' => 'xsd:boolean'), $gen->getReturnType());
    }

    public function testItExtractReturnTypeArrayOfString(): void
    {
        $gen = $this->givenGenerator('returnArrayOfString');
        $this->assertEquals(array('returnArrayOfString' => 'tns:ArrayOfstring'), $gen->getReturnType());
    }

    public function testItThrowAnExceptionOnUnknownTypes(): void
    {
        $this->expectException(\Exception::class);
        $this->givenGenerator('returnUnknownType');
    }

    public function testItAsksToPluginsForUnknownTypes(): void
    {
        $plugin = new class
        {
            public function wsdlDoc2soapTypes($params)
            {
                $params['doc2soap_types'] = array_merge($params['doc2soap_types'], array(
                    'arrayofplugintypes' => 'tns:ArrayOfStats'
                ));
            }
        };
        EventManager::instance()->addListener(Event::WSDL_DOC2SOAP_TYPES, $plugin, 'wsdlDoc2soapTypes', false);

        $gen = $this->givenGenerator('returnArrayOfPluginTypes');
        $this->assertEquals(array('returnArrayOfPluginTypes' => 'tns:ArrayOfStats'), $gen->getReturnType());
    }

    private function givenTheCommentOfAddProject(): string
    {
        $gen = $this->givenGenerator('addProject');
        return $gen->getComment();
    }

    private function givenTheParametersOfAddProject(): array
    {
        $gen = $this->givenGenerator('addProject');
        return $gen->getParameters();
    }

    private function givenTheReturnTypeOfAddProject(): array
    {
        $gen = $this->givenGenerator('addProject');
        return $gen->getReturnType();
    }

    private function givenGenerator($methodName): SOAP_WSDLMethodGenerator
    {
        $object = new class
        {
            /**
             * Create a new project
             *
             * This method throw an exception if there is a conflict on names or
             * it there is an error during the creation process.
             * It assumes a couple of things:
             * * The project type is "Project" (Not modifiable)
             * * The template is the default one (project id 100).
             * * There is no "Project description" nor any "Project description
             * * fields" (long desc, patents, IP, other software)
             * * The project services are inherited from the template
             * * There is no trove cat selected
             * * The default Software Policy is "Site exchange policy".
             *
             * Projects are automatically accepted
             *
             * * @todo DO stuff
             *
             * @param String  $requesterLogin Login of the user on behalf of who you create the project
             * @param String  $shortName      Unix name of the project
             * @param String  $realName       Full name of the project
             * @param String  $privacy        Either 'public' or 'private'
             * @param int $templateId Id of template project
             *
             * @return int The ID of newly created project
             */
            public function addProject($requesterLogin, $shortName, $realName, $privacy, $templateId)
            {
            }

            /**
             * @return bool
             */
            public function returnBoolean()
            {
            }

            /**
             * @return ArrayOfString
             */
            public function returnArrayOfString()
            {
            }

            /**
             * @return ArrayOfTrucsZarb
             */
            public function returnUnknownType()
            {
            }

            /**
             * @return ArrayOfPluginTypes
             */
            public function returnArrayOfPluginTypes()
            {
            }
        };

        $class = new ReflectionClass(get_class($object));
        return new SOAP_WSDLMethodGenerator($class->getMethod($methodName));
    }
}
