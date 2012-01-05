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
class SOAP_WSDLMethodGenerator {
    /**
     * @var ReflectionMethod
     */
    private $method;
    
    private $comment    = '';
    private $parameters = array();
    private $returnType = array();
    
    public function __construct(ReflectionMethod $method) {
        $this->method = $method;
        $this->parseDocComment();
    }
    
    public function getComment() {
        return $this->comment;
    }
    
    public function getParameters() {
        return $this->parameters;
    }
    
    public function getReturnType() {
        return $this->returnType;
    }
    
    private function parseDocComment() {
        foreach ($this->getCommentLines() as $line) {
            $line = $this->removeCommentsBorders($line);
            $this->parseDescription($line);
            $this->parseParameters($line);
            $this->parseReturnType($line);
        }
    }
    
    private function removeCommentsBorders($line) {
        $line = trim($line);
        $line = preg_replace('%^/\*\*%', '', $line);
        $line = preg_replace('%^\*/%', '', $line);
        $line = preg_replace('%^\*%', '', $line);
        return $line;
    }
    
    private function parseDescription($line) {
        if ($this->lineDoesntContainPhpDoc($line)) {
            $this->comment .= trim($line).PHP_EOL;
        }
    }
    
    private function lineDoesntContainPhpDoc($line) {
        return ($this->isNotPresentInLine($line, '@param') &&
                $this->isNotPresentInLine($line, '@return') &&
                $this->isNotPresentInLine($line, '@see'));
    }
    
    private function isNotPresentInLine($line, $token) {
        return strpos($line, $token) === false;
    }
    
    private function parseParameters($line) {
        $matches = array();
        if (preg_match('%@param[ \t]+([^ \t]*)[ \t]+([^ \t]*)[ \t]+.*%', $line, $matches)) {
            $this->parameters[$this->docParamToSoap($matches[2])] = $this->docTypeToSoap($matches[1]);
        }
    }
    
    private function parseReturnType($line) {
        if (preg_match('%@return[ \t]+([^ \t]*)%', $line, $matches)) {
            $this->returnType = array($this->method->getName() => $this->docTypeToSoap($matches[1]));
        }
    }
    
    private function getCommentLines() {
        return explode(PHP_EOL, $this->method->getDocComment());
    }
    
    private function docParamToSoap($paramName) {
        return substr($paramName, 1);
    }
    
    private function docTypeToSoap($docType) {
        switch(strtolower($docType)) {
            case 'string':
                return 'xsd:string';
            case 'integer':
            case 'int':
                return 'xsd:int';
            case 'boolean':
            case 'bool':
                return 'xsd:boolean';
        }
    }
}

?>
