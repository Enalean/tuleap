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

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

class CitationStripperTest extends TestCase
{

    private $fixtures_dir;
    /** @var Tracker_Artifact_MailGateway_CitationStripper */
    private $citation_stripper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures_dir      = dirname(__FILE__) . '/_fixtures';
        $this->citation_stripper = new Tracker_Artifact_MailGateway_CitationStripper();
    }

    public function testItStripsCitationFromTextContent(): void
    {
        $parsed_text_content          = file_get_contents($this->fixtures_dir . '/expected_followup.text.txt');
        $text_content_witout_citation = file_get_contents($this->fixtures_dir . '/expected_followup_without_citation.text.txt');

        $this->assertSame(
            $text_content_witout_citation,
            $this->citation_stripper->stripText($parsed_text_content),
        );
    }

    public function testItStripsCitationFromHTMLContent(): void
    {
        $parsed_text_content          = file_get_contents($this->fixtures_dir . '/expected_followup.html.txt');
        $text_content_witout_citation = file_get_contents($this->fixtures_dir . '/expected_followup_without_citation.html.txt');

        $this->assertSame(
            $text_content_witout_citation,
            $this->citation_stripper->stripHTML($parsed_text_content),
        );
    }

    public function testItStripsCitationFromFrenchOutlook(): void
    {
        $parsed_text_content          = file_get_contents($this->fixtures_dir . '/outlook_quote_fr.txt');
        $text_content_witout_citation = file_get_contents($this->fixtures_dir . '/expected_followup_outlook_quote_fr.txt');

        $this->assertSame(
            $text_content_witout_citation,
            $this->citation_stripper->stripText($parsed_text_content),
        );
    }

    public function testItStripsCitationFromEnglishOutlook(): void
    {
        $parsed_text_content          = file_get_contents($this->fixtures_dir . '/outlook_quote_en.txt');
        $text_content_witout_citation = file_get_contents($this->fixtures_dir . '/expected_followup_outlook_quote_en.txt');

        $this->assertSame(
            $text_content_witout_citation,
            $this->citation_stripper->stripText($parsed_text_content),
        );
    }

    public function testItStripsCitationFromOutlookWhereNewLineAreNotCRFLF(): void
    {
        $parsed_text_content = file_get_contents($this->fixtures_dir . '/outlook_quote_no_crlf.txt');
        $expected_text       = file_get_contents($this->fixtures_dir . '/expected_outlook_quote_no_crlf.txt');

        $this->assertSame(
            $expected_text,
            $this->citation_stripper->stripText($parsed_text_content),
        );
    }
}
