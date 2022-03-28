<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;

final class NewArtifactLinkInitialChangesetValueBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID                 = 405;
    private const FIRST_ADDED_ARTIFACT_ID  = 66;
    private const SECOND_ADDED_ARTIFACT_ID = 460;
    private const PARENT_ARTIFACT_ID       = 165;

    private function build(array $payload): NewArtifactLinkInitialChangesetValue
    {
        $builder = new NewArtifactLinkInitialChangesetValueBuilder();

        $link_field = new \Tracker_FormElement_Field_ArtifactLink(
            self::FIELD_ID,
            55,
            1,
            'irrelevant',
            'Irrelevant',
            'Irrelevant',
            true,
            'P',
            false,
            '',
            1
        );

        return $builder->buildFromPayload($link_field, $payload);
    }

    public function dataProviderInvalidPayloads(): array
    {
        return [
            'Payload is empty'                      => [[]],
            'Payload has none of the required keys' => [['invalid_key' => []]],
            'Links key does not contain an array'   => [['links' => null]],
        ];
    }

    public function testItBuildsFromARESTPayload(): void
    {
        $payload = [
            'links' => [
                ['id' => self::FIRST_ADDED_ARTIFACT_ID],
                ['id' => self::SECOND_ADDED_ARTIFACT_ID, 'type' => 'custom_type'],
            ],
            'parent' => ['id' => self::PARENT_ARTIFACT_ID],
        ];

        $value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertNotNull($value->getParent());
        self::assertSame(self::PARENT_ARTIFACT_ID, $value->getParent()->getParentArtifactId());

        $new_links = $value->getNewLinks();
        self::assertCount(2, $new_links->getArtifactLinks());
        [$first_link, $second_link] = $new_links->getArtifactLinks();
        self::assertSame(self::FIRST_ADDED_ARTIFACT_ID, $first_link->getTargetArtifactId());
        self::assertNull($first_link->getType());
        self::assertSame(self::SECOND_ADDED_ARTIFACT_ID, $second_link->getTargetArtifactId());
        self::assertSame('custom_type', $second_link->getType());
    }

    public function testItBuildsFromARESTPayloadWithOnlyParentKey(): void
    {
        $payload = [
            'parent' => ['id' => self::PARENT_ARTIFACT_ID],
        ];

        $value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertNotNull($value->getParent());
        self::assertSame(self::PARENT_ARTIFACT_ID, $value->getParent()->getParentArtifactId());
        self::assertEmpty($value->getNewLinks()->getArtifactLinks());
    }

    public function testItBuildsFromARESTPayloadWithOnlyLinksKey(): void
    {
        $payload = [
            'links' => [
                ['id' => self::FIRST_ADDED_ARTIFACT_ID],
                ['id' => self::SECOND_ADDED_ARTIFACT_ID, 'type' => 'custom_type'],
            ],
        ];

        $value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertNull($value->getParent());

        $new_links = $value->getNewLinks();
        self::assertCount(2, $new_links->getArtifactLinks());
        [$first_link, $second_link] = $new_links->getArtifactLinks();
        self::assertSame(self::FIRST_ADDED_ARTIFACT_ID, $first_link->getTargetArtifactId());
        self::assertNull($first_link->getType());
        self::assertSame(self::SECOND_ADDED_ARTIFACT_ID, $second_link->getTargetArtifactId());
        self::assertSame('custom_type', $second_link->getType());
    }

    /**
     * @dataProvider dataProviderInvalidPayloads
     */
    public function testItThrowsWhenPayloadHasNoneOfTheRequiredKeys(array $payload): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->build($payload);
    }
}
