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

use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Artifact_ChangesetJsonFormatterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    public function testItHasJsonRepresentation(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $timestamp = mktime(1, 1, 1, 9, 25, 2013);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class, [15, $artifact, 45, $timestamp, ''])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $template_renderer = \Mockery::spy(\TemplateRenderer::class);
        $template_renderer->shouldReceive('renderToString')->andReturn('body');

        $json_formatter = new Tracker_Artifact_ChangesetJsonFormatter($template_renderer);

        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getPreference');
        $current_user->shouldReceive('getLocale');
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
