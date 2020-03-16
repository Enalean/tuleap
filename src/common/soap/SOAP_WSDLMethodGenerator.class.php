<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Parse a PHP Method and generate SOAP WSDL compatible method description.
 */
class SOAP_WSDLMethodGenerator
{
    /**
     * @var ReflectionMethod
     */
    private $method;

    private $comment    = '';
    private $parameters = array();
    private $returnType = array();

    /**
     * @var array map to know a soap type corresponding to a phpdoc type
     */
    private $doc2soap_types = array(
        'string'                   => 'xsd:string',
        'integer'                  => 'xsd:int',
        'int'                      => 'xsd:int',
        'boolean'                  => 'xsd:boolean',
        'bool'                     => 'xsd:boolean',
        'arrayofstring'            => 'tns:ArrayOfstring',
        'arrayofrevision'          => 'tns:ArrayOfRevision',
        'arrayofcommiter'          => 'tns:ArrayOfCommiter',
        'arrayofsvnpathinfo'       => 'tns:ArrayOfSvnPathInfo',
        'arrayofsvnpathdetails'    => 'tns:ArrayOfSvnPathDetails',
        'arrayofuserinfo'          => 'tns:ArrayOfUserInfo',
        'arrayofdescfields'        => 'tns:ArrayOfDescFields',
        'arrayofdescfieldsvalues'  => 'tns:ArrayOfDescFieldsValues',
        'arrayofservicesvalues'    => 'tns:ArrayOfServicesValues',
        'userinfo'                 => 'tns:UserInfo',
    );

    public function __construct(ReflectionMethod $method)
    {
        $this->method = $method;
        $this->augmentDoc2SoapTypes();
        $this->parseDocComment();
    }

    private function augmentDoc2SoapTypes()
    {
        EventManager::instance()->processEvent(
            Event::WSDL_DOC2SOAP_TYPES,
            array(
                'doc2soap_types' => &$this->doc2soap_types
            )
        );
    }

    /**
     * Return the textual comment of the method
     *
     * @return String
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Return a HTML formated version of the textual comment
     *
     * @return String
     */
    public function getHTMLFormattedComment()
    {
        return nl2br(trim($this->comment));
    }

    /**
     * Return the parameters of the method ready (Nusoap SOAP compatible format)
     *
     * @return Array of String
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Return the return type of the method (Nusoap SOAP compatible format)
     *
     * @return Array
     */
    public function getReturnType()
    {
        return $this->returnType;
    }

    /**
     * Loop arround the method comment and parse it
     */
    private function parseDocComment()
    {
        foreach ($this->getCommentLines() as $line) {
            $line = $this->removeCommentsBorders($line);
            $this->parseDescription($line);
            $this->parseParameters($line);
            $this->parseReturnType($line);
        }
    }

    /**
     * Clean the line (remove "phpdoc borders")
     *
     * @param String $line
     *
     * @return String
     */
    private function removeCommentsBorders($line)
    {
        $line = trim($line);
        $line = preg_replace('%^/\*\*%', '', $line);
        $line = preg_replace('%^\*/%', '', $line);
        $line = preg_replace('%^\*%', '', $line);
        return $line;
    }

    /**
     * Detect and store if the line belongs to description
     *
     * @param String $line
     */
    private function parseDescription($line)
    {
        if ($this->lineDoesntContainPhpDoc($line)) {
            $this->comment .= trim($line) . PHP_EOL;
        }
    }

    /**
     * Return true if the line doesn't contain a phpdoc tag
     *
     * @param String $line
     *
     * @return bool
     */
    private function lineDoesntContainPhpDoc($line)
    {
        return ($this->isNotPresentInLine($line, '@return') &&
                $this->isNotPresentInLine($line, '@todo') &&
                $this->isNotPresentInLine($line, '@see'));
    }

    /**
     * Return True if the token doesn't appear in line
     *
     * @param String $line
     * @param String $token
     *
     * @return bool
     */
    private function isNotPresentInLine($line, $token)
    {
        return strpos($line, $token) === false;
    }

    /**
     * Detect and store phpdoc parameters
     *
     * @param String $line
     */
    private function parseParameters($line)
    {
        $matches = array();
        if (preg_match('%@param[ \t]+([^ \t]*)[ \t]+([^ \t]*)[ \t]+.*%', $line, $matches)) {
            $this->parameters[$this->docParamToSoap($matches[2])] = $this->docTypeToSoap($matches[1]);
        }
    }

    /**
     * Detect and store return type
     *
     * @param String $line
     */
    private function parseReturnType($line)
    {
        if (preg_match('%@return[ \t]+([^ \t]*)%', $line, $matches)) {
            $this->returnType = array($this->method->getName() => $this->docTypeToSoap($matches[1]));
        }
    }

    /**
     * Split the text comment in lines
     *
     * @return Array of String
     */
    private function getCommentLines()
    {
        return explode(PHP_EOL, $this->method->getDocComment());
    }

    /**
     * Transform phpdoc param name to a soap name
     */
    private function docParamToSoap($paramName)
    {
        return substr($paramName, 1);
    }

    /**
     * Convert phpdoc type to soap type
     *
     * @param String $docType
     *
     * @return String
     */
    private function docTypeToSoap($docType)
    {
        if (isset($this->doc2soap_types[strtolower($docType)])) {
            return $this->doc2soap_types[strtolower($docType)];
        }
        throw new Exception("Unknown type $docType");
    }
}
