<?php
/*
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

namespace Tuleap\Tracker\Artifact\XML\Exporter;

use EventManager;
use PFUser;
use Tracker_XML_ChildrenCollector;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueComputedXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ExternalExporterCollector;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueArtifactLinkXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueDateXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueFileXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueFloatXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueIntegerXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueListXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueOpenListXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValuePermissionsOnArtifactXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueStringXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueTextXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueUnknownXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

class ArtifactXMLExporterBuilder
{
    public function build(
        Tracker_XML_ChildrenCollector $children_collector,
        FilePathXMLExporter $file_path_xml_exporter,
        PFUser $current_user,
        UserXMLExporter $user_xml_exporter,
        bool $is_in_archive_context,
    ): ArtifactXMLExporter {
        $file_info_xml_exporter = new FileInfoXMLExporter($file_path_xml_exporter);

        $visitor            = new ChangesetValueXMLExporterVisitor(
            new ChangesetValueDateXMLExporter(
                new FieldChangeDateBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new ChangesetValueFileXMLExporter($file_info_xml_exporter),
            new ChangesetValueFloatXMLExporter(
                new FieldChangeFloatBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new ChangesetValueIntegerXMLExporter(),
            new ChangesetValueStringXMLExporter(
                new FieldChangeStringBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new ChangesetValueTextXMLExporter(
                new FieldChangeTextBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new ChangesetValuePermissionsOnArtifactXMLExporter(),
            new ChangesetValueListXMLExporter(
                new FieldChangeListBuilder(
                    new XML_SimpleXMLCDATAFactory(),
                    $user_xml_exporter
                )
            ),
            new ChangesetValueOpenListXMLExporter($user_xml_exporter),
            new ChangesetValueArtifactLinkXMLExporter(
                $children_collector,
                $current_user
            ),
            new ChangesetValueComputedXMLExporter($current_user, $is_in_archive_context),
            new ChangesetValueUnknownXMLExporter(),
            new ExternalExporterCollector(EventManager::instance())
        );
        $values_exporter    = new ChangesetValuesXMLExporter($visitor, $is_in_archive_context);
        $changeset_exporter = new ChangesetXMLExporter(
            $values_exporter,
            $user_xml_exporter
        );

        return new ArtifactXMLExporter($changeset_exporter, $file_info_xml_exporter);
    }
}
