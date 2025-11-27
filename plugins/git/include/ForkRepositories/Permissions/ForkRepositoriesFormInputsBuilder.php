<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\ForkRepositories\Permissions;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use Tuleap\HTTPRequest;
use MalformedPathException;
use Tuleap\Git\ForkRepositories\ForkPathContainsDoubleDotsFault;
use Tuleap\Git\PathJoinUtil;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class ForkRepositoriesFormInputsBuilder
{
    public function __construct(private TreeMapper $object_mapper)
    {
    }

    /**
     * @return Ok<ForkRepositoriesFormInputs>|Err<Fault>
     */
    public function fromRequest(HTTPRequest $request, \PFUser $user): Ok|Err
    {
        try {
            $inputs = $this->object_mapper->map(
                ForkRepositoriesFormInputs::class,
                [
                    'repositories_ids' => $request->get('repos'),
                    'destination_project_id' => $request->get('to_project') ?: null,
                    'path' => $request->get('path') ?: '',
                    'fork_type' => $request->get('choose_destination'),
                ],
            );

            if (empty($inputs->repositories_ids)) {
                return Result::Err(MissingRequiredParametersFault::build());
            }

            try {
                PathJoinUtil::userRepoPath($user->getUserName(), $inputs->path);
            } catch (MalformedPathException $e) {
                return Result::err(ForkPathContainsDoubleDotsFault::build());
            }

            return Result::Ok($inputs);
        } catch (MappingError $e) {
            return Result::Err(MissingRequiredParametersFault::build());
        }
    }
}
