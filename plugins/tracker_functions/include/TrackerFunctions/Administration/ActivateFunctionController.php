<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Administration;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\DispatchablePSR15Compatible;

final class ActivateFunctionController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly UpdateFunctionActivation $function_activation,
        private readonly LogFunctionActivated $activated_logs,
        private readonly LogFunctionDeactivated $deactivated_logs,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tracker = $request->getAttribute(\Tracker::class);
        if (! $tracker instanceof \Tracker) {
            throw new \LogicException('Tracker is missing');
        }

        $user = $request->getAttribute(\PFUser::class);
        if (! $user instanceof \PFUser) {
            throw new \LogicException('PFUser is missing');
        }

        $parsed_body = $request->getParsedBody();

        if (is_array($parsed_body) && isset($parsed_body['activate-function']) && $parsed_body['activate-function']) {
            $this->function_activation->activateFunction($tracker->getId());
            $this->activated_logs->logFunctionActivated($user, $tracker);

            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                AdministrationController::getUrl($tracker),
                NewFeedback::success(dgettext('tuleap-tracker_functions', 'The function has been activated')),
            );
        } else {
            $this->function_activation->deactivateFunction($tracker->getId());
            $this->deactivated_logs->logFunctionDeactivated($user, $tracker);

            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                AdministrationController::getUrl($tracker),
                NewFeedback::success(dgettext('tuleap-tracker_function', 'The function has been deactivated')),
            );
        }
    }

    public static function getUrl(\Tracker $tracker): string
    {
        return '/tracker_functions/' . urlencode((string) $tracker->getId()) . '/admin/activate';
    }
}
