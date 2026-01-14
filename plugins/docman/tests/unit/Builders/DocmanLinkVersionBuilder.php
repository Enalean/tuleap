<?php
/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Builders;

final class DocmanLinkVersionBuilder
{
    private int $id           = 123;
    private int $user_id      = 101;
    private int $item_id      = 456;
    private int $number       = 23;
    private string $label     = 'my version';
    private string $changelog = 'my log';
    private int $date         = 123456789;
    private string $link_url  = 'https://example.com';

    private function __construct()
    {
    }

    public static function aLinkVersion(): self
    {
        return new self();
    }

    public function withNumber(int $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function build(): \Docman_LinkVersion
    {
        return new \Docman_LinkVersion([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'item_id' => $this->item_id,
            'number' => $this->number,
            'label' => $this->label,
            'changelog' => $this->changelog,
            'date' => $this->date,
            'link_url' => $this->link_url,
        ]);
    }
}
