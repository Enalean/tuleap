<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

abstract class XML_ParseException extends Exception // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct(string $message, private string $rng_path, private array $indented_xml)
    {
        parent::__construct($message);
    }

    public function getRngPath(): string
    {
        return $this->rng_path;
    }

    abstract public function getErrors(): array;

    public function getSourceXMLForError(XML_ParseError $error): string
    {
        return $this->indented_xml[$error->getLine() - 1];
    }

    public function getIndentedXml(): string
    {
        $output  = [];
        $line_no = 1;
        foreach ($this->indented_xml as $line) {
            $output[] = $line_no . ': ' . $line;
            $line_no++;
        }
        return implode(PHP_EOL, $output);
    }

    public function getXMLWithoutLineNumbers(): string
    {
        return implode(PHP_EOL, $this->indented_xml);
    }

    public function getFileLines(): array
    {
        return $this->indented_xml;
    }
}
