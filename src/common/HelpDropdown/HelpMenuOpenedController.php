<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\HelpDropdown;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class HelpMenuOpenedController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    private const METRIC_NAME = 'help_menu_opened_total';
    private const HELP        = 'Total number of times the help menu has been opened';

    private \UserManager $user_manager;
    private Prometheus $prometheus;
    private ResponseFactoryInterface $response_factory;

    public function __construct(
        \UserManager $user_manager,
        Prometheus $prometheus,
        ResponseFactoryInterface $response_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->prometheus       = $prometheus;
        $this->user_manager     = $user_manager;
        $this->response_factory = $response_factory;
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->prometheus->increment(self::METRIC_NAME, self::HELP);

        $current_user = $this->user_manager->getCurrentUser();
        $current_user->setPreference('has_release_note_been_seen', '1');

        return $this->response_factory->createResponse(204);
    }
}
