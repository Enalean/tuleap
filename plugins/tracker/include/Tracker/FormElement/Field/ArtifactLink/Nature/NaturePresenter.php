<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

class NaturePresenter
{

    public const FORWARD_LABEL = 'forward';
    public const REVERSE_LABEL = 'reverse';

    /**
     * @var string
     */
    public $reverse_label;
    /**
     * @var string
     */
    public $forward_label;
    /**
     * @var string
     */
    public $shortname;
    /**
     * @var bool
     */
    public $is_system = false;
    /**
     * @var bool
     */
    public $is_visible;

    public function __construct(string $shortname, string $forward_label, string $reverse_label, bool $is_visible)
    {
        $this->shortname           = $shortname;
        $this->forward_label       = $forward_label;
        $this->reverse_label       = $reverse_label;
        $this->is_visible          = $is_visible;
    }
}
