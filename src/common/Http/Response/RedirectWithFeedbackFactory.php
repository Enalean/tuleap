<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Http\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Layout\Feedback\NewFeedback;

class RedirectWithFeedbackFactory
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var FeedbackSerializer
     */
    private $feedback_serializer;

    public function __construct(ResponseFactoryInterface $response_factory, FeedbackSerializer $feedback_serializer)
    {
        $this->response_factory    = $response_factory;
        $this->feedback_serializer = $feedback_serializer;
    }

    public function createResponseForUser(
        \PFUser $user,
        string $redirect_to,
        NewFeedback $new_feedback
    ): ResponseInterface {
        $this->feedback_serializer->serialize($user, $new_feedback);
        return $this->response_factory->createResponse(302)
            ->withHeader('Location', $redirect_to);
    }
}
