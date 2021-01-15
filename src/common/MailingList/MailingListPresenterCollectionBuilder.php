<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\MailingList;

use HTTPRequest;
use IProvideDataAccessResult;
use Project;

class MailingListPresenterCollectionBuilder
{
    /**
     * @var MailingListPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(MailingListPresenterBuilder $presenter_builder)
    {
        $this->presenter_builder = $presenter_builder;
    }

    /**
     * @return MailingListPresenter[]
     */
    public function build(IProvideDataAccessResult $mailing_lists_result, Project $project, HTTPRequest $request): array
    {
        $mailing_list_presenters = [];
        foreach ($mailing_lists_result as $row) {
            $mailing_list_presenters[] = $this->presenter_builder->buildFromRow($row, $project, $request);
        }

        return $mailing_list_presenters;
    }
}
