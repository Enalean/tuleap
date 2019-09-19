<?php
/**
 * Copyright (c) Enalean SAS, 2016. All rights reserved
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

namespace Tuleap\Markdown;

use Michelf\MarkdownExtra;
use Codendi_HTMLPurifier;

class ContentInterpretor
{

    public function getInterpretedContent($markdown_content)
    {

        return Codendi_HTMLPurifier::instance()->purify(
            MarkdownExtra::defaultTransform($markdown_content),
            CODENDI_PURIFIER_FULL
        );
    }
}
