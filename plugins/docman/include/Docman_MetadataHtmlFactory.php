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
class Docman_MetadataHtmlFactory
{
    public static function getFromMetadata($md, $formParams)
    {
        $mdh = \null;
        switch ($md->getLabel()) {
            case 'owner':
                $mdh = new \Docman_MetadataHtmlOwner($md, $formParams);
                break;
            case 'obsolescence_date':
                $mdh = new \Docman_MetadataHtmlObsolescence($md, $formParams);
                break;
        }
        if ($mdh === \null) {
            switch ($md->getType()) {
                case \PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                    $mdh = new \Docman_MetadataHtmlText($md, $formParams);
                    break;
                case \PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                    $mdh = new \Docman_MetadataHtmlString($md, $formParams);
                    break;
                case \PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                    $mdh = new \Docman_MetadataHtmlDate($md, $formParams);
                    break;
                case \PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                    $mdh = new \Docman_MetadataHtmlList($md, $formParams);
                    break;
                default:
            }
        }
        return $mdh;
    }
    public function buildFieldArray($mdIter, $mdla, $whitelist, $formName, $themePath)
    {
        $fields = [];
        $formParams = ['form_name' => $formName, 'theme_path' => $themePath];
        foreach ($mdIter as $md) {
            if ($whitelist && isset($mdla[$md->getLabel()]) || ! $whitelist && ! isset($mdla[$md->getLabel()])) {
                $fields[$md->getLabel()] = self::getFromMetadata($md, $formParams);
            }
        }
        return $fields;
    }
}
