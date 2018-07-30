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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\REST\JsonCast;
use Codendi_HTMLPurifier;

class CommentRepresentation
{

    /** @var int */
    public $id;

    /** @var Tuleap\User\REST\MinimalUserRepresentation */
    public $user;

    /**
     * @var string {@type string}
     */
    public $post_date;

    /** @var string */
    public $content;

    /** @var string */
    public $type;


    public function build($id, $project_id, $user_representation, $post_date, $content)
    {
        $this->id        = $id;
        $this->user      = $user_representation;
        $this->post_date = JsonCast::toDate($post_date);
        $purifier        = Codendi_HTMLPurifier::instance();
        $this->content   = $purifier->purify($content, CODENDI_PURIFIER_LIGHT, $project_id);
        $this->type      = 'comment';
    }
}
