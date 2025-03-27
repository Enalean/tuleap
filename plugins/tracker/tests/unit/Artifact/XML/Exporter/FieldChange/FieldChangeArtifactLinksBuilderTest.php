<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\XML\Exporter\FieldChange;


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldChangeArtifactLinksBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FieldChangeArtifactLinksBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new FieldChangeArtifactLinksBuilder(new \XML_SimpleXMLCDATAFactory());
    }

    public function testItExportBareLinks(): void
    {
        $changeset_node = new \SimpleXMLElement('<changeset/>');

        $this->builder->build(
            $changeset_node,
            'Links',
            [
                new ArtifactLinkChange(10089), new ArtifactLinkChange(10090),
            ]
        );

        self::assertNotNull($changeset_node->field_change);
        self::assertEquals('art_link', (string) $changeset_node->field_change['type']);
        self::assertEquals('Links', (string) $changeset_node->field_change['field_name']);
        self::assertCount(2, $changeset_node->field_change->value);
        self::assertEquals('10089', (string) $changeset_node->field_change->value[0]);
        self::assertEquals('10090', (string) $changeset_node->field_change->value[1]);
    }

    public function testItExportLinksWithType(): void
    {
        $changeset_node = new \SimpleXMLElement('<changeset/>');

        $this->builder->build(
            $changeset_node,
            'Links',
            [
                new ArtifactLinkChange(10089, 'child'), new ArtifactLinkChange(10090),
            ]
        );

        self::assertNotNull($changeset_node->field_change);
        self::assertEquals('art_link', (string) $changeset_node->field_change['type']);
        self::assertEquals('Links', (string) $changeset_node->field_change['field_name']);
        self::assertCount(2, $changeset_node->field_change->value);
        self::assertEquals('10089', (string) $changeset_node->field_change->value[0]);
        self::assertEquals('child', (string) $changeset_node->field_change->value[0]['nature']);
        self::assertEquals('10090', (string) $changeset_node->field_change->value[1]);
        self::assertFalse(isset($changeset_node->field_change->value[1]['nature']));
    }
}
