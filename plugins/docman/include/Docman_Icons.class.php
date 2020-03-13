<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
 * Folder is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Icons
{
    public $images_path;
    public function __construct($images_path)
    {
        $this->images_path = $images_path;
    }

    public function getActionIcon($action)
    {
        switch ($action) {
            case 'popup':
                return $this->images_path . 'popup.png';
                break;
            case 'newFolder':
                return $this->images_path . 'folder-new.png';
                break;
            case 'newDocument':
                return $this->images_path . 'document-new.png';
                break;
            case 'details':
                return $this->images_path . 'item-details.png';
                break;
            case 'show':
                return $this->images_path . 'folder-show.png';
                break;
            default:
                break;
        }
    }

    public function getIconForItem(&$item, $params = null)
    {
        $icon = $this->images_path;
        if (isset($params['icon_width'])) {
                $icon .= $params['icon_width'] . '/';
        }
        switch (strtolower(get_class($item))) {
            case 'docman_folder':
                $icon .= 'folder';
                if (isset($params['expanded']) && $params['expanded']) {
                    $icon .= '-open';
                }
                break;
            case 'docman_link':
                $icon .= 'link';
                break;
            case 'docman_wiki':
                $icon .= 'wiki';
                break;
            case 'docman_file':
            case 'docman_embeddedfile':
                $v = $item->getCurrentVersion();
                $type = $v ? $v->getFiletype() : null;
                $icon .= $this->getIconForMimeType($type);
                break;
            case 'docman_empty':
                $icon .= 'empty';
                break;
            default:
                $icon .= 'binary';
                break;
        }
        $icon .= '.png';
        return $icon;
    }

    /**
    *
    *
    * @see http://www.ltsw.se/knbase/internet/mime.htp
    * @see http://framework.openoffice.org/documentation/mimetypes/mimetypes.html
    * @see http://filext.com/
    */
    private function getIconForMimeType($mime_type)
    {
        $parts = explode('/', strtolower($mime_type));
        switch ($parts[0]) {
            case 'audio':
            case 'video':
            case 'image':
                $icon = $parts[0];
                break;
            case 'text':
                if (isset($parts[1]) && $parts[1] == 'html') {
                    $icon = 'html';
                } else {
                    $icon = 'text';
                }
                break;
            case 'application':
                $icon = 'binary';
                if (isset($parts[1])) {
                    switch ($parts[1]) {
                        case 'gzip':
                        case 'zip':
                        case 'x-tar':
                        case 'x-java-archive':
                        case 'x-gzip':
                        case 'x-gtar':
                        case 'x-compressed':
                            $icon = 'archive';
                            break;
                        case 'pdf':
                            $icon = $parts[1];
                            break;
                        case 'rtf':
                        case 'msword':
                        case 'vnd.ms-works':
                        case 'vnd.openxmlformats-officedocument.wordprocessingml.document':
                        case 'word':
                        case 'wordperfect5.1':
                        case 'vnd.ms-word.document.macroenabled.12':
                        case 'vnd.oasis.opendocument.text':
                        case 'vnd.oasis.opendocument.text-template':
                        case 'vnd.oasis.opendocument.text-web':
                        case 'vnd.oasis.opendocument.text-master':
                        case 'x-vnd.oasis.opendocument.text':
                        case 'vnd.sun.xml.writer':
                        case 'vnd.sun.xml.writer.template':
                        case 'vnd.sun.xml.writer.global':
                        case 'vnd.stardivision.writer':
                        case 'vnd.stardivision.writer-global':
                        case 'x-starwriter':
                        case 'x-soffice':
                            $icon = 'document';
                            break;
                        case 'powerpoint':
                        case 'vnd.ms-powerpoint':
                        case 'vnd.ms-powerpoint.presentation.macroenabled.12':
                        case 'vnd.openxmlformats-officedocument.presentationml.presentation':
                        case 'vnd.sun.xml.impress':
                        case 'vnd.sun.xml.impress.template':
                        case 'vnd.oasis.opendocument.presentation':
                        case 'vnd.oasis.opendocument.presentation-template':
                        case 'vnd.stardivision.impress':
                        case 'vnd.stardivision.impress-packed':
                        case 'x-starimpress':
                            $icon = 'presentation';
                            break;
                        case 'excel':
                        case 'vnd.ms-excel':
                        case 'vnd.ms-excel.sheet.macroenabled.12':
                        case 'vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                        case 'vnd.sun.xml.calc':
                        case 'vnd.sun.xml.calc.template':
                        case 'vnd.oasis.opendocument.spreadsheet':
                        case 'vnd.oasis.opendocument.spreadsheet-template':
                        case 'vnd.stardivision.calc':
                        case 'x-starcalc':
                            $icon = 'spreadsheet';
                            break;
                        default:
                            break;
                    }
                }
                break;
            default:
                $icon = 'binary';
        }
        return $icon;
    }

    public function getFolderSpinner()
    {
        return $this->images_path . 'folder-spinner.gif';
    }
    public function getSpinner()
    {
        return $this->images_path . 'spinner.gif';
    }
    public function getIcon($icon)
    {
        return $this->images_path . $icon;
    }

    public function getThemeIcon($icon)
    {
        return util_get_image_theme('ic/' . $icon);
    }
}
