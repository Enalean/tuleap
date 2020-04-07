<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata\ListOfValuesElement;

use Docman_MetadataListOfValuesElement;
use Docman_MetadataListOfValuesElementDao;

class MetadataListOfValuesElementListBuilder
{
    /**
     * @var Docman_MetadataListOfValuesElementDao
     */
    private $dao;

    public function __construct(Docman_MetadataListOfValuesElementDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return Docman_MetadataListOfValuesElement[]
     */
    public function build(int $id, bool $only_active): array
    {
        $dar = $this->dao->searchByFieldId($id, $only_active);
        $list_of_elements = [];
        foreach ($dar as $list_value) {
            $element = new Docman_MetadataListOfValuesElement();
            $element->initFromRow($list_value);

            $list_of_elements[] = $element;
        }
        return $list_of_elements;
    }
}
