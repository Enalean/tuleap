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
use SimpleXMLElement;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

class XMLFileContentRetriever
{
    /**
     * @return Ok<SimpleXMLElement>|Err<Fault>
     */
    public function getSimpleXMLElementFromFilePath(string $file_path): Ok|Err
    {
        $xml_contents = file_get_contents($file_path);
        if ($xml_contents === false) {
            return Result::err(Fault::fromMessage('Got unexpected failure while loading xml file'));
        }

        return $this->getSimpleXMLElementFromString($xml_contents);
    }

    /**
     * @return Ok<SimpleXMLElement>|Err<Fault>
     */
    public function getSimpleXMLElementFromString(string $file_contents): Ok|Err
    {
        if (empty($file_contents)) {
            return Result::err(InvalidXMLContentFault::fromEmptyContent());
        }

        return $this->checkFileIsValidXML($file_contents)
            ->andThen(
                /**
                 * @return Ok<SimpleXMLElement>|Err<Fault>
                 */
                function () use ($file_contents): Ok|Err {
                    $xml = simplexml_load_string($file_contents, 'SimpleXMLElement', $this->getLibXMLOptions());

                    if ($xml === false) {
                        return Result::err(Fault::fromMessage('Got unexpected failure while loading XML content'));
                    }

                    return Result::ok($xml);
                }
            );
    }

    /**
     * @psalm-param non-empty-string $file_contents
     *
     * @return Ok<true>|Err<Fault>
     */
    private function checkFileIsValidXML(string $file_contents): Ok|Err
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = new DOMDocument();
        $xml->loadXML($file_contents, $this->getLibXMLOptions());
        $errors = libxml_get_errors();

        if (! empty($errors)) {
            return Result::err(InvalidXMLContentFault::fromLibXMLErrors($errors));
        }

        return Result::ok(true);
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
