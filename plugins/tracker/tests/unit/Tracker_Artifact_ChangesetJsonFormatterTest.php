<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_ChangesetJsonFormatterTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    use \Tuleap\GlobalLanguageMock;

    public function testItHasJsonRepresentation(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');

        $artifact  = ArtifactTestBuilder::anArtifact(101)->build();
        $timestamp = mktime(1, 1, 1, 9, 25, 2013);

        $changeset = ChangesetTestBuilder::aChangeset(15)
            ->submittedBy(45)
            ->submittedOn($timestamp)
            ->withTextComment('')
            ->ofArtifact($artifact)
            ->build();

        $template_renderer = $this->createMock(\TemplateRenderer::class);
        $template_renderer->method('renderToString')->willReturn('body');

        $json_formatter = new Tracker_Artifact_ChangesetJsonFormatter($template_renderer);

        $current_user = UserTestBuilder::buildWithDefaults();
        $this->assertEquals(
            $json_formatter->format($changeset, $current_user),
            [
                'id'           => 15,
                'submitted_by' => 45,
                'submitted_on' => date('c', $timestamp),
                'email'        => '',
                'html'         => 'body',
            ]
        );
    }
}
