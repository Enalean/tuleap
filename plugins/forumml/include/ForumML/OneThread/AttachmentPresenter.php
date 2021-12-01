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

namespace Tuleap\ForumML\OneThread;

/**
 * @psalm-immutable
 */
final class AttachmentPresenter
{
    /**
     * @var int
     */
    public $id_attachment;
    /**
     * @var string
     */
    public $file_name;
    /**
     * @var string
     */
    public $file_path;
    /**
     * @var string
     */
    public $url;

    public function __construct(
        int $id_attachment,
        string $file_name,
        string $file_path,
        string $url,
    ) {
        $this->id_attachment = $id_attachment;
        $this->file_name     = $file_name;
        $this->file_path     = $file_path;
        $this->url           = $url;
    }
}
