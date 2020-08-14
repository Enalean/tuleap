<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\REST\v1;

use Tuleap\Project\REST\MinimalProjectRepresentation;

/**
 * @psalm-immutable
 */
class SvnRepositoryRepresentationBase
{
    public const ROUTE = 'svn';

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var MinimalProjectRepresentation {@type \Tuleap\Project\REST\MinimalProjectRepresentation}
     */
    public $project;

    /**
     * @var string {@type string}
     */
    public $uri;

    /**
     * @var string {@type string}
     */
    public $name;

    /**
     * @var string {@type string}
     */
    public $svn_url;

    protected function __construct()
    {
    }
}
