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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Artifact_MailGateway_CitationStripperTest extends TuleapTestCase {

    public function itStripsCitationFromTextContent() {
        $fixtures_dir = dirname(__FILE__) .'/_fixtures';

        $parsed_text_content          = file_get_contents($fixtures_dir .'/expected_followup.text.txt');
        $text_content_witout_citation = file_get_contents($fixtures_dir .'/expected_followup_without_citation.text.txt');

        $citation_stripper = new Tracker_Artifact_MailGateway_CitationStripper();
        $this->assertIdentical(
            $citation_stripper->stripText($parsed_text_content),
            $text_content_witout_citation
        );
    }

    public function itStripsCitationFromHTMLContent() {
        $fixtures_dir = dirname(__FILE__) .'/_fixtures';

        $parsed_text_content          = file_get_contents($fixtures_dir .'/expected_followup.html.txt');
        $text_content_witout_citation = file_get_contents($fixtures_dir .'/expected_followup_without_citation.html.txt');

        $citation_stripper = new Tracker_Artifact_MailGateway_CitationStripper();
        $this->assertIdentical(
            $citation_stripper->stripHTML($parsed_text_content),
            $text_content_witout_citation
        );
    }
}