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
 *
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

use HTTPRequest;
use LDAP;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Valid_String;

class GroupAutocompleteController implements DispatchableWithRequest
{
    /**
     * @var LDAP
     */
    private $ldap;

    public function __construct(LDAP $ldap)
    {
        $this->ldap = $ldap;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $group_list   = [];
        $more_results = false;

        $vGroupName = new Valid_String('ldap_group_name');
        $vGroupName->required();
        if ($request->valid($vGroupName)) {
            $lri = $this->ldap->searchGroupAsYouType($request->get('ldap_group_name'), 15);
            if ($lri !== false) {
                while ($lri->valid()) {
                    $lr           = $lri->current();
                    $common_name  = $lr->getGroupCommonName();
                    $display_name = $lr->getGroupDisplayName();

                    $group_list[] = [
                        'id' => $common_name,
                        'text' => $display_name,
                    ];
                    $lri->next();
                }
                if ($this->ldap->getErrno() == LDAP::ERR_SIZELIMIT) {
                    $more_results = true;
                }
            }
        }

        $output = [
            'results'    => $group_list,
            'pagination' => [
                'more' => $more_results,
            ],
        ];

        $layout->sendJSON($output);
    }
}
