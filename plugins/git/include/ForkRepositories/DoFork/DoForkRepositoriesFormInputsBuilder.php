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

namespace Tuleap\Git\ForkRepositories\DoFork;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use MalformedPathException;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Git\ForkRepositories\ForkPathContainsDoubleDotsFault;
use Tuleap\Git\ForkRepositories\Permissions\MissingRequiredParametersFault;
use Tuleap\Git\PathJoinUtil;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class DoForkRepositoriesFormInputsBuilder
{
    public function __construct(private TreeMapper $object_mapper)
    {
    }

    /**
     * @return Ok<DoPersonalForkFormInputs>|Err<Fault>
     */
    public function buildForPersonalFork(ServerRequestInterface $request, \PFUser $user): Ok|Err
    {
            return $this->getParsedBody($request)->andThen(
                function (array $parsed_body) use ($user) {
                    try {
                        $inputs = $this->object_mapper->map(
                            DoPersonalForkFormInputs::class,
                            [
                                'fork_path' => $parsed_body['path'] ?: '',
                                'repositories_ids' => $parsed_body['repos'],
                                'permissions' => $parsed_body['repo_access'],
                            ],
                        );

                        if ($inputs->repositories_ids === '') {
                            return Result::err(MissingRequiredParametersFault::build());
                        }

                        try {
                            PathJoinUtil::userRepoPath($user->getUserName(), $inputs->fork_path);
                        } catch (MalformedPathException $e) {
                            return Result::err(ForkPathContainsDoubleDotsFault::build());
                        }

                        return Result::ok($inputs);
                    } catch (MappingError $e) {
                        return Result::err(MissingRequiredParametersFault::build());
                    }
                }
            );
    }

    /**
     * @return Ok<DoCrossProjectsForkFormInputs>|Err<Fault>
     */
    public function buildForCrossProjectsFork(ServerRequestInterface $request): Ok|Err
    {
        return $this->getParsedBody($request)->andThen(
            function (array $parsed_body) {
                try {
                    $inputs = $this->object_mapper->map(
                        DoCrossProjectsForkFormInputs::class,
                        [
                            'destination_project_id' => $parsed_body['to_project'],
                            'repositories_ids' => $parsed_body['repos'],
                            'permissions' => $parsed_body['repo_access'],
                        ],
                    );

                    if ($inputs->repositories_ids === '' || $inputs->destination_project_id === '') {
                        return Result::err(MissingRequiredParametersFault::build());
                    }

                    return Result::ok($inputs);
                } catch (MappingError $e) {
                    return Result::err(MissingRequiredParametersFault::build());
                }
            }
        );
    }

    /**
     * @return Ok<array<array-key, mixed>>|Err<Fault>
     */
    private function getParsedBody(ServerRequestInterface $request): Ok|Err
    {
        $parsed_body = $request->getParsedBody();
        if (! \is_array($parsed_body)) {
            return Result::err(MissingRequiredParametersFault::build());
        }

        return Result::ok($parsed_body);
    }
}
