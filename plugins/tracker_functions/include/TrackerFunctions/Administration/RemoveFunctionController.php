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
use PFUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\TrackerFunctions\Logs\DeleteLogsPerTracker;
use Tuleap\TrackerFunctions\WASM\WASMFunctionPathHelper;

final class RemoveFunctionController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly LogFunctionRemoved $history_saver,
        private readonly WASMFunctionPathHelper $function_path_helper,
        private readonly UpdateFunctionActivation $function_activation,
        private readonly DeleteLogsPerTracker $delete_logs,
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

        $path = $this->function_path_helper->getPathForTracker($tracker);
        if (! is_readable($path)) {
            return $this->redirectWithFeedback(
                $user,
                $tracker,
                NewFeedback::error(dgettext('tuleap-tracker_functions', 'Function does not exist, maybe it has already been removed?'))
            );
        }

        if (! unlink($path)) {
            return $this->redirectWithFeedback(
                $user,
                $tracker,
                NewFeedback::error(dgettext('tuleap-tracker_functions', 'Unable to remove the function'))
            );
        }

        $this->function_activation->deactivateFunction($tracker->getId());
        $this->history_saver->logFunctionRemoved($user, $tracker);
        $this->delete_logs->deleteLogsPerTracker($tracker->getId());

        return $this->redirectWithFeedback(
            $user,
            $tracker,
            NewFeedback::success(dgettext('tuleap-tracker_functions', 'The function has been removed'))
        );
    }

    private function redirectWithFeedback(PFUser $user, \Tracker $tracker, NewFeedback $feedback): ResponseInterface
    {
        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            AdministrationController::getUrl($tracker),
            $feedback,
        );
    }

    public static function getUrl(\Tracker $tracker): string
    {
        return '/tracker_functions/' . urlencode((string) $tracker->getId()) . '/admin/remove';
    }
}
