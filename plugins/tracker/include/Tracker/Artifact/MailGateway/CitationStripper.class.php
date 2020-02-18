<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

class Tracker_Artifact_MailGateway_CitationStripper
{

    public const TEXT_CITATION_PATTERN = '/(\n>\s+.*)+/';
    public const HTML_CITATION_PATTERN = '%<blockquote[^>]*>.*</blockquote>%';
    public const DEFAULT_REPLACEMENT   = "\n[citation removed]";

    private $outlook_header = array(
        'en' => array(
            'from'    => 'From:',
            'sent'    => 'Sent:',
            'to'      => 'To:',
            'subject' => 'Subject:',
        ),
        'fr' => array(
            'from'    => 'De :',
            'sent'    => 'Envoyé :',
            'to'      => 'À :',
            'subject' => 'Objet :',
        ),
    );

    public function stripText($mail_content)
    {
        return $this->stripOutlookTextQuote(
            $this->stripStandardTextQuote($mail_content)
        );
    }

    private function stripStandardTextQuote($mail_content)
    {
        return preg_replace(
            self::TEXT_CITATION_PATTERN,
            self::DEFAULT_REPLACEMENT,
            $mail_content
        );
    }

    private function stripOutlookTextQuote($mail_content)
    {
        return $this->stripOutlook(
            $this->stripOutlook($mail_content, 'en'),
            'fr'
        );
    }

    public function stripHTML($mail_content)
    {
        $doc = new DOMDocument();
        $doc->loadHTML($mail_content);
        $this->removeBlockquoteElements($doc);

        return $this->getContentInsideBody($doc);
    }

    private function removeBlockquoteElements(DOMDocument $doc)
    {
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//div[@class="gmail_extra"]') as $blockquote) {
            $this->removeNode($blockquote);
        }

        foreach ($xpath->query('//blockquote') as $blockquote) {
            $this->removeNode($blockquote);
        }
    }

    private function removeNode(DOMNode $node)
    {
        if ($node->parentNode !== null) {
            $node->parentNode->removeChild($node);
        }
    }

    private function getContentInsideBody(DOMDocument $doc)
    {
        $xml = simplexml_import_dom($doc);
        $content = '';
        foreach ($xml->body->children() as $child) {
            $content .= $child->asXML();
        }

        return $content;
    }

    private function stripOutlook($body, $lang)
    {
        $stripped_body = $this->stripOutlookAccordingToNewLine($body, $lang, "\r\n");
        if ($stripped_body === $body) {
            $stripped_body = $this->stripOutlookAccordingToNewLine($body, $lang, "\n");
        }
        return $stripped_body;
    }

    /**
     * @return string
     */
    private function stripOutlookAccordingToNewLine($body, $lang, $new_line)
    {
        $pos_from    = strpos($body, $new_line . $this->outlook_header[$lang]['from']);
        $pos_sent    = strpos($body, $new_line . $this->outlook_header[$lang]['sent'], $pos_from);
        $pos_to      = strpos($body, $new_line . $this->outlook_header[$lang]['to'], $pos_sent);
        $pos_subject = strpos($body, $new_line . $this->outlook_header[$lang]['subject'], $pos_to);
        $pos_body    = strpos($body, $new_line . $new_line, $pos_subject);

        if ($pos_from !== false && $pos_sent !== false && $pos_to !== false && $pos_subject !== false && $pos_body !== false) {
            return substr($body, 0, $pos_from);
        }
        return $body;
    }
}
