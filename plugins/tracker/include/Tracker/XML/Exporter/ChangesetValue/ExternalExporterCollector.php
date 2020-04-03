<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace  Tuleap\Tracker\XML\Exporter\ChangesetValue;

use EventManager;
use Tracker_Artifact_ChangesetValue;
use Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter;

class ExternalExporterCollector
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function collectExporter(Tracker_Artifact_ChangesetValue $changeset_value): ?Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter
    {
        $external_exporter_getter = new GetExternalExporter($changeset_value);
        $this->event_manager->processEvent($external_exporter_getter);

        return $external_exporter_getter->getExporter();
    }
}
