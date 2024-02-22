<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\RepositoryPullRequests;

use CuyZ\Valinor\Mapper\MappingError;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Criterion\MalformedQueryFault;
use Tuleap\PullRequest\Criterion\SearchCriteria;

/**
 * I convert a json query string to a SearchCriteria.
 */
class QueryToSearchCriteriaConverter
{
    /**
     * @return Ok<SearchCriteria> | Err<Fault>
     */
    public function convert(string $query): Ok | Err
    {
        if ($query === '') {
            return Result::ok(new SearchCriteria(null));
        }

        try {
            $search_criteria = (new \CuyZ\Valinor\MapperBuilder())
                ->mapper()
                ->map(
                    SearchCriteria::class,
                    new \CuyZ\Valinor\Mapper\Source\JsonSource($query)
                );

            if (count($search_criteria->authors) > 1) {
                return Result::err(MalformedQueryFault::onlyOneItemAccepted('authors'));
            }

            return Result::ok($search_criteria);
        } catch (MappingError $mapping_error) {
            return Result::err($this->buildFaultFromMappingError($mapping_error));
        } catch (\Exception $exception) {
            return Result::err(MalformedQueryFault::build());
        }
    }

    private function buildFaultFromMappingError(MappingError $mapping_error): Fault
    {
        return MalformedQueryFault::buildFromMappingErrors(
            \CuyZ\Valinor\Mapper\Tree\Message\Messages::flattenFromNode($mapping_error->node())->errors()
        );
    }
}
