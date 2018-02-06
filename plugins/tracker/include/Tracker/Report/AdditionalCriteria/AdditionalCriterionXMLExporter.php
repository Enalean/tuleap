<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\AdditionalCriteria;

use SimpleXMLElement;
use Tracker_Report_AdditionalCriterion;
use XML_SimpleXMLCDATAFactory;

class AdditionalCriterionXMLExporter
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_factory;

    public function __construct(XML_SimpleXMLCDATAFactory $cdata_factory)
    {
        $this->cdata_factory = $cdata_factory;
    }

    public function exportToXML(Tracker_Report_AdditionalCriterion $comment_criterion, SimpleXMLElement $xml)
    {
        $xml_additional_criteria = $xml->addChild('additional_criteria');
        $xml_additional_criteria->addAttribute('name', (string) $comment_criterion->getKey());
        $this->cdata_factory->insert($xml_additional_criteria, 'value', (string) $comment_criterion->getValue());
    }
}
