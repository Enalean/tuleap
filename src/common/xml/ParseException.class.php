<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class XML_ParseException extends Exception
{
    /** @var XML_ParseError[] */
    private $errors;
    private $indented_xml;
    private $rng_path;

    public function __construct($rng_path, array $errors, array $indented_xml)
    {
        $this->rng_path     = $rng_path;
        $this->errors       = $errors;
        $this->indented_xml = $indented_xml;
        parent::__construct('XML parse errors');
    }

    public function getRngPath()
    {
        return $this->rng_path;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSourceXMLForError(XML_ParseError $error)
    {
        return $this->indented_xml[$error->getLine() - 1];
    }

    public function getIndentedXml()
    {
        $output = array();
        $line_no = 1;
        foreach ($this->indented_xml as $line) {
            $output[] = $line_no . ': ' . $line;
            $line_no++;
        }
        return implode(PHP_EOL, $output);
    }

    public function getFileLines()
    {
        return $this->indented_xml;
    }
}
