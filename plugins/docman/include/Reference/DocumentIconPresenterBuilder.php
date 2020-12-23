<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Reference;

use Docman_Icons;

final class DocumentIconPresenterBuilder
{
    public function buildForItem(\Docman_Item $item): DocumentIconPresenter
    {
        switch ($this->getIconWithoutPngExtension($item)) {
            case 'folder':
                return new DocumentIconPresenter('fa fa-folder', 'inca-silver');
            case 'link':
                return new DocumentIconPresenter('fa fa-link', 'flamingo-pink');
            case 'audio':
                return new DocumentIconPresenter('far fa-file-audio', 'lake-placid-blue');
            case 'video':
                return new DocumentIconPresenter('far fa-file-video', 'ocean-turquoise');
            case 'image':
                return new DocumentIconPresenter('far fa-file-image', 'graffiti-yellow');
            case 'text':
                return new DocumentIconPresenter('far fa-file-alt', 'inca-silver');
            case 'code':
                return new DocumentIconPresenter('far fa-file-code', 'daphne-blue');
            case 'archive':
                return new DocumentIconPresenter('far fa-file-archive', 'plum-crazy');
            case 'pdf':
                return new DocumentIconPresenter('far fa-file-pdf', 'fiesta-red');
            case 'document':
                return new DocumentIconPresenter('far fa-file-word', 'deep-blue');
            case 'presentation':
                return new DocumentIconPresenter('far fa-file-powerpoint', 'clockwork-orange');
            case 'spreadsheet':
                return new DocumentIconPresenter('far fa-file-excel', 'sherwood-green');
            case 'empty':
            default:
                return new DocumentIconPresenter('far fa-file', 'firemist-silver');
        }
    }

    private function getIconWithoutPngExtension(\Docman_Item $item): string
    {
        $docman_icons = new Docman_Icons('');

        return (string) substr($docman_icons->getIconForItem($item), 0, -4);
    }
}
