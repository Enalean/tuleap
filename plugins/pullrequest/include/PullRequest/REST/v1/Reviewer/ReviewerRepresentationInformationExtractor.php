<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Reviewer;

use Luracast\Restler\RestException;
use Tuleap\Project\REST\UserRESTReferenceRetriever;

final class ReviewerRepresentationInformationExtractor
{
    /**
     * @var UserRESTReferenceRetriever
     */
    private $user_rest_reference_retriever;

    public function __construct(UserRESTReferenceRetriever $user_rest_reference_retriever)
    {
        $this->user_rest_reference_retriever   = $user_rest_reference_retriever;
    }

    /**
     * @return \PFUser[]
     *
     * @throws RestException
     */
    public function getUsers(ReviewersPUTRepresentation $representation): array
    {
        $users = [];

        foreach ($representation->users as $user_representation) {
            $user = $this->user_rest_reference_retriever->getUserFromReference(
                $user_representation
            );

            if ($user === null) {
                throw new RestException(
                    400,
                    "User with reference $user_representation is not known"
                );
            }

            $users[] = $user;
        }

        return $users;
    }
}
