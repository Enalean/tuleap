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

namespace Tuleap\Cardwall;

use Tracker_FormElementFactory;
use Tuleap\Cardwall\Semantic\FieldUsedInSemanticObjectChecker;

class AllowedFieldRetriever
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var FieldUsedInSemanticObjectChecker
     */
    private $semantic_field_checker;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        FieldUsedInSemanticObjectChecker $semantic_field_checker
    ) {
        $this->form_element_factory   = $form_element_factory;
        $this->semantic_field_checker = $semantic_field_checker;
    }

    public function retrieveAllowedFieldType(\Tracker_FormElement_Field $field)
    {
        if (! $this->semantic_field_checker->isUsedInBackgroundColorSemantic($field)) {
            return [];
        }

        switch ($this->form_element_factory->getType($field)) {
            case 'sb':
                return ['rb'];
                break;
            case 'rb':
                return ['sb'];
                break;
            default:
                return [];
                break;
        }
    }
}
