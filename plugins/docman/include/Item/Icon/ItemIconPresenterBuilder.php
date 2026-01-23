<?php
/*
 * Copyright (c) Enalean, 2018 - Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
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

namespace Tuleap\Docman\Item\Icon;

use Docman_Item;
use Docman_VersionFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class ItemIconPresenterBuilder
{
    private const string DEFAULT_ICON = 'fa-regular fa-file item-icon-color';
    public const string LINK_ICON     = 'fa-solid fa-link item-icon-color';
    public const string WIKI_ICON     = 'fa-brands fa-wikipedia-w item-icon-color';
    public const string EMBEDDED_ICON = 'fa-solid fa-file-lines item-icon-color';
    public const string EMPTY_ICON    = 'fa-regular fa-file item-icon-color';
    public const string FOLDER_ICON   = 'fa-regular fa-folder-open item-icon-color';

    public function __construct(
        private EventDispatcherInterface $event_manager,
        private Docman_VersionFactory $version_factory,
    ) {
    }

    public function buildForItem(\Docman_Item $item, ?array $params = null): ItemIconPresenter
    {
        $icon_for_item = $this->getIconForItem($item, $params);
        return $this->event_manager
            ->dispatch(new ItemIconPresenterEvent($icon_for_item, $item))
            ->getPresenter();
    }

    private function getIconForItem(Docman_Item $item, ?array $params = null): ItemIconPresenter
    {
        switch (strtolower($item::class)) {
            case 'docman_folder':
                $icon = 'fa-regular fa-folder item-icon-color';
                if (isset($params['expanded']) && $params['expanded']) {
                    $icon = self::FOLDER_ICON;
                }
                $icon_presenter = new ItemIconPresenter($icon, 'inca-silver');
                break;
            case 'docman_link':
                $icon_presenter = new ItemIconPresenter(self::LINK_ICON, 'flamingo-pink');
                break;
            case 'docman_wiki':
                $icon_presenter = new ItemIconPresenter(self::WIKI_ICON, 'inca-silver');
                break;
            case 'docman_embeddedfile':
                $icon_presenter = new ItemIconPresenter(self::EMBEDDED_ICON, 'inca-silver');
                break;
            case 'docman_file':
                assert($item instanceof \Docman_File);
                $v = $item->getCurrentVersion();
                if ($v === null) {
                    $v = $this->version_factory->getCurrentVersionForItem($item);
                }
                $type           = ($v !== null) ? $v->getFiletype() : '';
                $icon_presenter =  $this->getIconPresenterForMimeType($type);
                break;
            case 'docman_empty':
                $icon_presenter =  new ItemIconPresenter(self::EMPTY_ICON, 'firemist-silver');
                break;
            default:
                $icon_presenter =  new ItemIconPresenter(self::DEFAULT_ICON, 'firemist-silver');
                break;
        }

        return $this->event_manager
            ->dispatch(new ItemIconPresenterEvent($icon_presenter, $item))
            ->getPresenter();
    }

    /**
     * @see http://www.ltsw.se/knbase/internet/mime.htp
     * @see http://framework.openoffice.org/documentation/mimetypes/mimetypes.html
     * @see http://filext.com/
     */
    public function getIconPresenterForMimeType(string $mime_type): ItemIconPresenter
    {
        $mime_type_lower = strtolower($mime_type);
        $parts           = explode('/', $mime_type_lower);
        $type            = $parts[0] ?? '';
        $sub_type        = $parts[1] ?? '';

        switch ($type) {
            case 'audio':
                return new ItemIconPresenter('fa-solid fa-file-audio item-icon-color', 'lake-placid-blue');
            case 'video':
                return new ItemIconPresenter('fa-solid fa-file-video item-icon-color', 'ocean-turquoise');
            case 'image':
                return new ItemIconPresenter('fa-solid fa-file-image item-icon-color', 'graffiti-yellow');
            case 'text':
                if ($sub_type === 'html') {
                    return new ItemIconPresenter('fa-solid fa-file-code item-icon-color', 'inca-silver');
                }
                return new ItemIconPresenter('fa-regular fa-file-lines item-icon-color', 'firemist-silver');
            case 'application':
                $icon = 'fa-solid fa-file-code document-code-icon';
                switch ($sub_type) {
                    case 'gzip':
                    case 'zip':
                    case 'x-tar':
                    case 'x-rar':
                    case 'x-java-archive':
                    case 'x-gzip':
                    case 'x-gtar':
                    case 'x-compressed':
                        return new ItemIconPresenter('fa-solid fa-file-zipper item-icon-color', 'plum-crazy');
                    case 'pdf':
                        return new ItemIconPresenter('fa-solid fa-file-pdf item-icon-color', 'fiesta-red');
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
                        return new ItemIconPresenter('fa-solid fa-file-word item-icon-color', 'deep-blue');
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
                        return new ItemIconPresenter('fa-solid fa-file-powerpoint item-icon-color', 'clockwork-orange');
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
                        return new ItemIconPresenter('fa-solid fa-file-excel item-icon-color', 'sherwood-green');
                }
                return new ItemIconPresenter($icon . ' item-icon-color', 'daphne-blue');
            default:
                return new ItemIconPresenter(self::DEFAULT_ICON, 'firemist-silver');
        }
    }
}
