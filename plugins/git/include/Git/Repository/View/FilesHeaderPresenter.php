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

namespace Tuleap\Git\Repository\View;

use GitRepository;

class FilesHeaderPresenter
{
    /** @var bool */
    public $can_display_selector;
    /** @var string */
    public $head_name;
    /** @var bool */
    public $is_tag;
    /** @var bool */
    public $is_undefined = false;
    /** @var int */
    public $committer_epoch;
    /** @var int */
    public $repository_id;
    /** @var string */
    public $repository_url;
    /**
     * @psalm-readonly
     */
    public ?string $repository_default_branch;
    /** @var array */
    public $json_encoded_parameters;

    public function __construct(
        GitRepository $repository,
        $repository_url,
        $can_display_selector,
        $head_name,
        $is_tag,
        $committer_epoch,
        array $url_parameters,
    ) {
        $this->repository_id             = $repository->getId();
        $this->repository_url            = $repository_url;
        $this->repository_default_branch = \Git_Exec::buildFromRepository($repository)->getDefaultBranch();
        $this->can_display_selector      = $can_display_selector;
        if ($head_name !== '') {
            $this->head_name = $head_name;
        } else {
            $this->head_name    = dgettext('tuleap-git', 'Undefined');
            $this->is_undefined = true;
        }
        $this->committer_epoch = $committer_epoch;
        $this->is_tag          = $is_tag;

        $this->json_encoded_parameters = json_encode($url_parameters);
    }
}
