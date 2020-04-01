<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

use Tuleap\Tracker\XML\Exporter\ChangesetValue\ChangesetValueComputedXMLExporter;
use Tuleap\Tracker\XML\Exporter\FileInfoXMLExporter;

class Tracker_XML_Exporter_ArtifactXMLExporterBuilder
{

    /** @var Tracker_XML_Exporter_ArtifactXMLExporter */
    public function build(
        Tracker_XML_ChildrenCollector $children_collector,
        Tracker_XML_Exporter_FilePathXMLExporter $file_path_xml_exporter,
        PFUser $current_user,
        UserXMLExporter $user_xml_exporter,
        $is_in_archive_context
    ) {
        $file_info_xml_exporter = new FileInfoXMLExporter($file_path_xml_exporter);

        $visitor = new Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor(
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueDateXMLExporter(),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter($file_info_xml_exporter),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter(),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueIntegerXMLExporter(),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueStringXMLExporter(),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporter(),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter(),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter($user_xml_exporter),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter($user_xml_exporter),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter(
                $children_collector,
                $current_user
            ),
            new ChangesetValueComputedXMLExporter($current_user, $is_in_archive_context),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter()
        );
        $values_exporter    = new Tracker_XML_Exporter_ChangesetValuesXMLExporter($visitor, $is_in_archive_context);
        $changeset_exporter = new Tracker_XML_Exporter_ChangesetXMLExporter(
            $values_exporter,
            $user_xml_exporter
        );

        return new Tracker_XML_Exporter_ArtifactXMLExporter($changeset_exporter, $file_info_xml_exporter);
    }
}
