<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

/**
 * @psalm-immutable
 */
final class AdditionalButtonLinkPresenter
{
    public string $link_label;
    public string $url;
    public string $icon;
    public string $id;
    public array $data;
    public string $data_test;
    public string $disabled_messages;
    public bool $is_disabled;

    public function __construct(
        string $link_label,
        string $url,
        string $data_test,
        ?string $icon = null,
        ?string $id = null,
        ?array $data = null,
        array $disabled_messages = [],
    ) {
        $this->link_label        = $link_label;
        $this->url               = $url;
        $this->icon              = $icon ?: '';
        $this->id                = $id ?: '';
        $this->data              = $data ?: [];
        $this->data_test         = $data_test;
        $this->disabled_messages = implode(", ", $disabled_messages);
        $this->is_disabled       = count($disabled_messages) > 0;
    }
}
