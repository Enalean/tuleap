<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\XML;

use DOMDocument;
use RuntimeException;
use SimpleXMLElement;

class XMLFileContentRetriever
{
    public function getSimpleXMLElementFromFilePath(string $file_path): SimpleXMLElement
    {
        $xml_contents = file_get_contents($file_path);

        return $this->getSimpleXMLElementFromString($xml_contents);
    }

    public function getSimpleXMLElementFromString(string $file_contents): SimpleXMLElement
    {
        $this->checkFileIsValidXML($file_contents);

        return simplexml_load_string($file_contents, 'SimpleXMLElement', $this->getLibXMLOptions());
    }

    private function checkFileIsValidXML($file_contents): void
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = new DOMDocument();
        $xml->loadXML($file_contents, $this->getLibXMLOptions());
        $errors = libxml_get_errors();

        if (! empty($errors)) {
            throw new RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_xml'));
        }
    }

    private function getLibXMLOptions(): int
    {
        if ($this->isAllowedToLoadHugeFiles()) {
            return LIBXML_PARSEHUGE;
        }

        return 0;
    }

    private function isAllowedToLoadHugeFiles(): bool
    {
        return defined('IS_SCRIPT') && IS_SCRIPT;
    }
}
