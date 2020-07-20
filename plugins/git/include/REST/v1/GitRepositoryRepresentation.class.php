<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1;

use GitRepository;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class GitRepositoryRepresentation
{
    public const ROUTE = 'git';

    public const FIELDS_BASIC = 'basic';
    public const FIELDS_ALL   = 'all';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $name;

    /**
     * The last part of the repository's name
     * For example:
     * name = tuleap/rhel6/stable
     * label = stable
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $path;

    /**
     * The repository's path without the project and without the .git suffix.
     * Used for presentation purposes to highlight a repository's label.
     * For example:
     * name = tuleap/rhel6/stable
     * path = myproject/tuleap/rhel6/stable.git
     * path_without_project = tuleap/rhel6
     *
     * Presentation will be:
     * tuleap/rhel6
     * <h1>stable</h1>
     *
     * @var string
     */
    public $path_without_project;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $last_update_date;

    /**
     * @var \Tuleap\Git\REST\v1\GitRepositoryPermissionRepresentation | null
     */
    public $permissions = null;

    /**
     * @var \Tuleap\Git\REST\v1\GerritServerRepresentation | null
     */
    public $server = null;

    /**
     * @var string
     */
    public $html_url;

    /**
     * @var array
     */
    public $additional_information;

    private function __construct(
        GitRepository $repository,
        string $repository_path,
        string $html_url,
        ?GerritServerRepresentation $server_representation,
        string $last_update_date,
        array $additional_information,
        ?GitRepositoryPermissionRepresentation $permissions
    ) {
        $this->id                     = JsonCast::toInt($repository->getId());
        $this->uri                    = self::ROUTE . '/' . $this->id;
        $this->name                   = $repository->getName();
        $this->label                  = $repository->getLabel();
        $this->path                   = $repository_path;
        $this->path_without_project   = $repository->getPathWithoutProject();
        $this->description            = $repository->getDescription();
        $this->server                 = $server_representation;
        $this->html_url               = $html_url;
        $this->last_update_date       = JsonCast::toDate($last_update_date);
        $this->additional_information = $additional_information;
        $this->permissions            = $permissions;
    }

    public static function build(
        GitRepository $repository,
        string $html_url,
        ?GerritServerRepresentation $server_representation,
        string $last_update_date,
        array $additional_information,
        ?GitRepositoryPermissionRepresentation $permissions
    ): self {
        return new self(
            $repository,
            $repository->getPath(),
            $html_url,
            $server_representation,
            $last_update_date,
            $additional_information,
            $permissions
        );
    }
}
