<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs;

use Tuleap\Layout\Feedback\ISerializeFeedback;
use Tuleap\Layout\Feedback\NewFeedback;

final class FeedbackSerializerStub implements ISerializeFeedback
{
    /**
     * @var NewFeedback[]
     */
    private array $captured_feedbacks = [];

    public function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function serialize(\PFUser $user, NewFeedback ...$feedbacks): void
    {
        foreach ($feedbacks as $feedback) {
            $this->captured_feedbacks[] = $feedback;
        }
    }

    /**
     * @return NewFeedback[]
     */
    public function getCapturedFeedbacks(): array
    {
        return $this->captured_feedbacks;
    }
}
