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

namespace Tuleap\Timetracking\JiraImporter;

use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use XML_SimpleXMLCDATAFactory;

class JiraXMLExport
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        XML_SimpleXMLCDATAFactory $cdata_factory,
        LoggerInterface $logger
    ) {
        $this->cdata_factory = $cdata_factory;
        $this->logger        = $logger;
    }

    public function exportJiraTimetracking(SimpleXMLElement $xml_tracker): void
    {
        $this->logger->debug("Export timetracking");

        $xml_timetracking = $xml_tracker->addChild('timetracking');
        $xml_timetracking->addAttribute('is_enabled', "1");

        $xml_timetracking_permissions      = $xml_timetracking->addChild('permissions');
        $xml_timetracking_permission_write = $xml_timetracking_permissions->addChild('write');

        $project_member_ugroup_name = ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS];
        $this->cdata_factory->insert(
            $xml_timetracking_permission_write,
            "ugroup",
            $project_member_ugroup_name
        );
    }
}
