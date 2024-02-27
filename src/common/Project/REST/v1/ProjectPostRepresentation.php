<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\REST\v1;

use Tuleap\Project\REST\v1\Project\ProjectFilePOSTRepresentation;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class ProjectPostRepresentation
{
    /**
     * @var string Name of the project {@from body}
     */
    public $shortname;

    /**
     * @var string Full description of the project {@from body} {@required false}
     */
    public $description;

    /**
     * @var string LA short description of the project {@from body}
     */
    public $label;

    /**
     * @var bool Define the visibility of the project {@from body}
     */
    public $is_public;

    /**
     * @var bool | null Define if the project should accept restricted users {@from body} {@required false}
     */
    public $allow_restricted;

    /**
     * @var int Template for this project. {@from body} {@required false} {@min 1}
     */
    public $template_id;

    /**
     * @var string Template name provided by the platform {@from body} {@required false}
     */
    public $xml_template_name;

    /**
     * @var array Categories to be set a project creation {@from body} {@required false} {@type \Tuleap\Project\REST\v1\CategoryPostRepresentation}
     */
    public $categories;

    /**
     * @var array Custom fields to be set a project creation {@from body} {@required false} {@type \Tuleap\Project\REST\v1\FieldsPostRepresentation}
     */
    public $fields;
    /**
     * @var \Tuleap\Project\REST\v1\Project\ProjectFilePOSTRepresentation | null Archive to use to create project {@from body} {@required false} {@type \Tuleap\Project\REST\v1\Project\ProjectFilePOSTRepresentation}
     */
    public ?ProjectFilePOSTRepresentation $from_archive = null;

    private function __construct(int $template_id)
    {
        $this->template_id = $template_id;
    }

    public static function build(int $template_id): self
    {
        return new self(
            JsonCast::toInt($template_id),
        );
    }
}
