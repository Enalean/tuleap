<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Item\Icon;

use Tuleap\Docman\Reference\DocumentIconPresenter;
use Tuleap\Event\Dispatchable;

final class DocumentIconPresenterEvent implements Dispatchable
{
    private DocumentIconPresenter $presenter;

    public function __construct(public readonly string $icon)
    {
        $this->presenter = match ($icon) {
            'folder' => new DocumentIconPresenter('fa fa-folder', 'inca-silver'),
            'link' => new DocumentIconPresenter('fa fa-link', 'flamingo-pink'),
            'audio' => new DocumentIconPresenter('far fa-file-audio', 'lake-placid-blue'),
            'video' => new DocumentIconPresenter('far fa-file-video', 'ocean-turquoise'),
            'image' => new DocumentIconPresenter('far fa-file-image', 'graffiti-yellow'),
            'text' => new DocumentIconPresenter('far fa-file-alt', 'inca-silver'),
            'code' => new DocumentIconPresenter('far fa-file-code', 'daphne-blue'),
            'archive' => new DocumentIconPresenter('far fa-file-archive', 'plum-crazy'),
            'pdf' => new DocumentIconPresenter('far fa-file-pdf', 'fiesta-red'),
            'document' => new DocumentIconPresenter('far fa-file-word', 'deep-blue'),
            'presentation' => new DocumentIconPresenter('far fa-file-powerpoint', 'clockwork-orange'),
            'spreadsheet' => new DocumentIconPresenter('far fa-file-excel', 'sherwood-green'),
            default => new DocumentIconPresenter('far fa-file', 'firemist-silver'),
        };
    }

    public function getPresenter(): DocumentIconPresenter
    {
        return $this->presenter;
    }

    public function setPresenter(DocumentIconPresenter $presenter): void
    {
        $this->presenter = $presenter;
    }
}
