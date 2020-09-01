<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_ValidateMetadataListIsNotEmpty extends \Docman_Validator
{
    public function __construct(&$metadata)
    {
        $msg = \sprintf(\dgettext('tuleap-docman', '"%1$s" is required, please fill the field.'), $metadata->getName());
        if ($metadata !== \null) {
            $selected_elements = [];
            $vIter = $metadata->getValue();
            $vIter->rewind();
            while ($vIter->valid()) {
                $current_value = $vIter->current();
                $selected_elements[] = $current_value->getId();
                $vIter->next();
            }
            if (! $this->metadataIsRequieredAndAtLeastOneValueIsSelected($metadata, $selected_elements)) {
                $this->addError($msg);
            }
        } else {
            $this->addError($msg);
        }
    }
    private function metadataIsRequieredAndAtLeastOneValueIsSelected(\Docman_ListMetadata $metadata, array $selectedElements)
    {
        if ($metadata->isEmptyAllowed()) {
            return \true;
        } elseif (\count($selectedElements) > 1) {
            return \true;
        } elseif (\count($selectedElements) === 1 && isset($selectedElements[0]) && $selectedElements[0] != 100) {
            return \true;
        } else {
            return \false;
        }
    }
}
