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

namespace Tuleap\Tracker\XML\Exporter\ChangesetValue;

use Tracker_Artifact_ChangesetValue;
use Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter;
use Tuleap\Event\Dispatchable;

class GetExternalExporter implements Dispatchable
{
    public const NAME = 'getExternalExporter';

    /**
     * @var Tracker_Artifact_ChangesetValue
     */
    private $changeset_value;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter | null
     */
    private $exporter;

    public function __construct(Tracker_Artifact_ChangesetValue $changeset_value)
    {
        $this->changeset_value = $changeset_value;
    }

    public function getChangesetValue(): Tracker_Artifact_ChangesetValue
    {
        return $this->changeset_value;
    }

    public function addExporter(Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter $exporter): void
    {
        $this->exporter = $exporter;
    }

    public function getExporter(): ?Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter
    {
        return $this->exporter;
    }
}
