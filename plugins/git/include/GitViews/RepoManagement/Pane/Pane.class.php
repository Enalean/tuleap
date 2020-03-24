<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use GitRepository;
use Codendi_Request;
use Codendi_HTMLPurifier;

/**
 * A pane to be displayed in git repo management
 */
abstract class Pane
{

    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var Codendi_Request
     */
    protected $request;
    /**
     * @var Codendi_HTMLPurifier
     */
    protected $hp;

    public function __construct(GitRepository $repository, Codendi_Request $request)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->hp         = Codendi_HTMLPurifier::instance();
    }

    /**
     * @return bool true if the pane can be displayed
     */
    public function canBeDisplayed()
    {
        return true;
    }

    public function getLabel()
    {
        return $this->getTitle();
    }

    /**
     * @return string eg: 'perms'
     */
    abstract public function getIdentifier();

    /**
     * @return string eg: 'Accesss Control'
     */
    abstract public function getTitle();

    /**
     * @return string eg: '<form>...</form>'
     */
    abstract public function getContent();
}
