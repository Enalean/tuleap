<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\File;

use DOMDocument;
use DOMElement;

class FileURLSubstitutor
{
    private const LIBXML_HTML_NODEFDTD = 4;

    public function substituteURLsInHTML(string $html, CreatedFileURLMapping $url_mapping): string
    {
        if ($html === '') {
            return $html;
        }

        if ($url_mapping->isEmpty()) {
            return $html;
        }

        $document = new DOMDocument();
        $previous_use_errors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $document->loadHTML($html, self::LIBXML_HTML_NODEFDTD);
        $loaded_without_any_errors = empty(libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($previous_use_errors);
        if (! $loaded_without_any_errors) {
            return $html;
        }

        $has_document_been_modified = $this->replaceOldURLsByNewOnes($url_mapping, $document);
        if (! $has_document_been_modified) {
            return $html;
        }

        $html = $document->saveHTML();

        return $this->trimHtmlAndBodyTags($html);
    }

    protected function replaceOldURLsByNewOnes(CreatedFileURLMapping $url_mapping, DOMDocument $document): bool
    {
        $images                     = $document->getElementsByTagName('img');
        $has_document_been_modified = false;
        foreach ($images as $image) {
            assert($image instanceof DOMElement);
            $new_src = $url_mapping->get($image->getAttribute('src'));
            if ($new_src !== null) {
                $image->setAttribute('src', $new_src);
                $has_document_been_modified = true;
            }
        }

        return $has_document_been_modified;
    }

    protected function trimHtmlAndBodyTags(string $html): string
    {
        $body_start_pos = strpos($html, '<body>');
        $body_end_pos   = strrpos($html, '</body>');
        if ($body_start_pos === false || $body_end_pos === false) {
            return $html;
        }

        $trim_start = $body_start_pos + strlen('<body>');
        $trim_end   = $body_end_pos - strlen($html);

        $trimmed = substr($html, $trim_start, $trim_end);
        if ($trimmed === false) {
            return $html;
        }

        return $trimmed;
    }
}
