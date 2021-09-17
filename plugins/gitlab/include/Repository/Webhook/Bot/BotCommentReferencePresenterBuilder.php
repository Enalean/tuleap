<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\Bot;

use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\ServerHostname;

class BotCommentReferencePresenterBuilder
{
    /**
     * @param WebhookTuleapReference[] $references
     *
     * @return BotCommentReferencePresenter[]
     */
    public function build(array $references): array
    {
        $comment_presenters = [];
        foreach ($references as $index => $reference) {
            $comment_presenters[] = new BotCommentReferencePresenter($reference->getId(), $this->getArtifactUrl($reference));
        }
        return $comment_presenters;
    }

    private function getArtifactUrl(WebhookTuleapReference $reference): string
    {
        return ServerHostname::HTTPSUrl() . '/plugins/tracker/?' . http_build_query(['aid' => $reference->getId()]);
    }
}
