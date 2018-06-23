<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'bootstrap.php';

class Tracker_Artifact_ChangesetJsonFormatterTest extends TuleapTestCase {

    public function itHasJsonRepresentation() {
        $timestamp = mktime(1,1,1,9,25,2013);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class, [15, aMockArtifact()->build(), 45, $timestamp, ''])->makePartial()->shouldAllowMockingProtectedMethods();
        $template_renderer = \Mockery::spy(\TemplateRenderer::class);
        stub($template_renderer)->renderToString()->returns('body');

        $json_formatter = new Tracker_Artifact_ChangesetJsonFormatter($template_renderer);

        $this->assertEqual(
            $json_formatter->format($changeset),
            array(
                'id'           => 15,
                'submitted_by' => 45,
                'submitted_on' => date('c', $timestamp),
                'email'        => '',
                'html'         => 'body',
            )
        );
    }
}
