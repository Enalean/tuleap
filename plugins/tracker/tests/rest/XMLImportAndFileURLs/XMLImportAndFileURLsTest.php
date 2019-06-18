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

namespace Tuleap\Tracker\Tests\REST\XMLImportAndFileURLs;

use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

class XMLImportAndFileURLsTest extends TrackerBase
{
    public function testImageSrcTargetsNewFileInsteadOfOldOneInHTMLText(): void
    {
        $artifact = $this->getArtifact();
        $file_url = $this->getURLOfTheFileAttachment($artifact);

        $html = $this->getOriginalSubmissionContent($artifact);
        $this->assertStringNotContainsString('/plugins/tracker/attachments/132-blank.gif', $html);
        $this->assertStringContainsString($file_url, $html);
        $this->assertStringContainsString('/plugins/tracker/attachments/133-attachment-that-will-be-deleted-later.gif', $html);

        $text = $this->getImplementationDetailsContent($artifact);
        $this->assertStringContainsString('/plugins/tracker/attachments/132-blank.gif', $text);
        $this->assertStringNotContainsString($file_url, $text);

        $comments = $this->getComments($artifact);
        $this->assertStringNotContainsString('/plugins/tracker/attachments/132-blank.gif', $comments[0]);
        $this->assertStringContainsString($file_url, $comments[0]);
        $this->assertStringContainsString('/plugins/tracker/attachments/132-blank.gif', $comments[1]);
        $this->assertStringNotContainsString($file_url, $comments[1]);
    }

    private function getArtifact(): array
    {
        $response = $this->getResponse(
            $this->client->get('trackers/' . $this->tracker_file_url_id . '/artifacts?values=all')
        );
        $this->assertEquals(200, $response->getStatusCode());

        $artifacts = $response->json();
        $this->assertCount(1, $artifacts);

        return $artifacts[0];
    }

    private function getURLOfTheFileAttachment(array $artifact): string
    {
        $file_url = '';
        foreach ($artifact['values'] as $value) {
            if ($value['label'] !== 'Attachment') {
                continue;
            }

            $this->assertCount(1, $value['file_descriptions']);
            $file_url = $value['file_descriptions'][0]['html_url'];
        }
        $this->assertNotEmpty($file_url);

        return $file_url;
    }

    private function getOriginalSubmissionContent(array $artifact): string
    {
        $html = '';
        foreach ($artifact['values'] as $value) {
            if ($value['label'] !== 'Original Submission') {
                continue;
            }

            $this->assertEquals('html', $value['format']);
            $html = $value['value'];
        }
        $this->assertNotEmpty($html);

        return $html;
    }

    private function getImplementationDetailsContent(array $artifact): string
    {
        $html = '';
        foreach ($artifact['values'] as $value) {
            if ($value['label'] !== 'Implementation details') {
                continue;
            }

            $this->assertEquals('text', $value['format']);
            $html = $value['value'];
        }
        $this->assertNotEmpty($html);

        return $html;
    }

    /**
     * @return string[]
     */
    private function getComments(array $artifact): array
    {
        $response = $this->getResponse(
            $this->client->get($artifact['changesets_uri'] . '?fields=comments')
        );
        $this->assertEquals(200, $response->getStatusCode());

        $changesets = $response->json();
        $this->assertCount(2, $changesets);
        return array_map(
            static function (array $changeset) {
                return $changeset['last_comment']['body'];
            },
            $changesets
        );
    }
}
