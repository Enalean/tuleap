<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\Secret;

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Gitlab\Repository\GitlabRepository;

class SecretChecker
{
    public const GITLAB_TOKEN_HEADER = 'X-Gitlab-Token';

    /**
     * @var SecretRetriever
     */
    private $secret_retriever;

    public function __construct(SecretRetriever $secret_retriever)
    {
        $this->secret_retriever = $secret_retriever;
    }

    /**
     * @throws SecretHeaderNotFoundException
     * @throws SecretNotDefinedException
     * @throws SecretHeaderNotMatchingException
     */
    public function checkSecret(
        GitlabRepository $gitlab_repository,
        ServerRequestInterface $http_request
    ): void {
        $webhook_secret_header = $http_request->getHeaderLine(self::GITLAB_TOKEN_HEADER);
        if ($webhook_secret_header === '') {
            throw new SecretHeaderNotFoundException();
        }

        $webhook_secret = $this->secret_retriever->getWebhookSecretForRepository(
            $gitlab_repository
        );

        if (! hash_equals($webhook_secret_header, $webhook_secret->getString())) {
            throw new SecretHeaderNotMatchingException();
        }
    }
}
