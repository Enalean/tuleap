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
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueDateXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueIntegerXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueStringXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_XML_Exporter_ArtifactXMLExporterBuilder
{
    public function build(
        Tracker_XML_ChildrenCollector $children_collector,
        Tracker_XML_Exporter_FilePathXMLExporter $file_path_xml_exporter,
        PFUser $current_user,
        UserXMLExporter $user_xml_exporter,
        $is_in_archive_context,
    ): Tracker_XML_Exporter_ArtifactXMLExporter {
        $file_info_xml_exporter = new FileInfoXMLExporter($file_path_xml_exporter);

        $visitor            = new Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor(
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueDateXMLExporter(
                new FieldChangeDateBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter($file_info_xml_exporter),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter(
                new FieldChangeFloatBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueIntegerXMLExporter(),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueStringXMLExporter(
                new FieldChangeStringBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporter(
                new FieldChangeTextBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter(),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter(
                new FieldChangeListBuilder(
                    new XML_SimpleXMLCDATAFactory(),
                    $user_xml_exporter
                )
            ),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter($user_xml_exporter),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter(
                $children_collector,
                $current_user
            ),
            new ChangesetValueComputedXMLExporter($current_user, $is_in_archive_context),
            new Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter(),
            new ExternalExporterCollector(EventManager::instance())
        );
        $values_exporter    = new Tracker_XML_Exporter_ChangesetValuesXMLExporter($visitor, $is_in_archive_context);
        $changeset_exporter = new Tracker_XML_Exporter_ChangesetXMLExporter(
            $values_exporter,
            $user_xml_exporter
        );

        return new Tracker_XML_Exporter_ArtifactXMLExporter($changeset_exporter, $file_info_xml_exporter);
    }
}
