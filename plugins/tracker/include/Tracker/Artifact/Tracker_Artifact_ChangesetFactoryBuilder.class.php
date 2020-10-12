<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class Tracker_Artifact_ChangesetFactoryBuilder
{
    public static function build(): Tracker_Artifact_ChangesetFactory
    {
        return new Tracker_Artifact_ChangesetFactory(
            new Tracker_Artifact_ChangesetDao(),
            new Tracker_Artifact_Changeset_ValueDao(),
            new Tracker_Artifact_Changeset_CommentDao(),
            new Tracker_Artifact_ChangesetJsonFormatter(TemplateRendererFactory::build()->getRenderer(dirname(TRACKER_BASE_DIR) . '/templates')),
            Tracker_FormElementFactory::instance()
        );
    }
}
