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

class Tracker_Artifact_MailGateway_CitationStripper {

    const TEXT_CITATION_PATTERN = '/(\n>\s+.*)+/';
    const HTML_CITATION_PATTERN = '%<blockquote[^>]*>.*</blockquote>%';
    const DEFAULT_REPLACEMENT   = "\n[citation removed]";

    public function stripText($mail_content) {
        return preg_replace(
            self::TEXT_CITATION_PATTERN,
            self::DEFAULT_REPLACEMENT,
            $mail_content
        );
    }

    public function stripHTML($mail_content) {
        $doc = new DOMDocument();
        $doc->loadHTML($mail_content);
        $this->removeBlockquoteElements($doc);

        return $this->getContentInsideBody($doc);
    }

    private function removeBlockquoteElements(DOMDocument $doc) {
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//div[@class="gmail_extra"]') as $blockquote) {
            $this->removeNode($blockquote);
        }

        foreach ($xpath->query('//blockquote') as $blockquote) {
            $this->removeNode($blockquote);
        }
    }

    private function removeNode(DOMNode $node) {
        $node->parentNode->removeChild($node);
    }

    private function getContentInsideBody(DOMDocument $doc) {
        $xml = simplexml_import_dom($doc);
        $content = '';
        foreach ($xml->body->children() as $child) {
            $content .= $child->asXML();
        }

        return $content;
    }
}