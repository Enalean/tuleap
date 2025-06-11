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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Contributor\XML;

use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReference;
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributor;
use Tuleap\Tracker\Semantic\XML\XMLSemantic;

final class XMLContributorSemantic extends XMLSemantic
{
    public function __construct(
        /**
         * @readonly
         */
        private XMLReference $reference,
    ) {
        parent::__construct(TrackerSemanticContributor::CONTRIBUTOR_SEMANTIC_SHORTNAME);
    }

    public function export(\SimpleXMLElement $parent_node, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $child = parent::export($parent_node, $form_elements);

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insert($child, 'shortname', TrackerSemanticContributor::CONTRIBUTOR_SEMANTIC_SHORTNAME);
        $cdata->insert($child, 'label', 'Contributor/assignee');
        $cdata->insert($child, 'description', 'Define the contributor/assignee of an artifact');
        $child->addChild('field')->addAttribute('REF', $this->reference->getId($form_elements));

        return $child;
    }
}
