<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchMeilisearch\Server\Administration;

use CSRFSynchronizerToken;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Config\ConfigUpdater;
use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\FullTextSearchMeilisearch\Server\IProvideCurrentKeyForLocalServer;
use Tuleap\FullTextSearchMeilisearch\Server\MeilisearchAPIKeyValidator;
use Tuleap\FullTextSearchMeilisearch\Server\MeilisearchIndexNameValidator;
use Tuleap\FullTextSearchMeilisearch\Server\MeilisearchServerURLValidator;
use Tuleap\FullTextSearchMeilisearch\Server\RemoteMeilisearchServerSettings;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

final class MeilisearchSaveAdminSettingsController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly IProvideCurrentKeyForLocalServer $local_meilisearch_server,
        private readonly CSRFSynchronizerToken $csrf_token,
        private readonly ConfigUpdater $config_set,
        private readonly MeilisearchServerURLValidator $server_url_validator,
        private readonly MeilisearchAPIKeyValidator $api_key_validator,
        private readonly MeilisearchIndexNameValidator $index_name_validator,
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $key_local_meilisearch_server = $this->local_meilisearch_server->getCurrentKey();
        $is_using_local_server        = $key_local_meilisearch_server !== null;
        if ($is_using_local_server) {
            throw new ForbiddenException("Cannot set url nor api key for a local meilisearch server");
        }

        $this->csrf_token->check();

        $body       = $request->getParsedBody();
        $server_url = (string) ($body['server_url'] ?? '');
        $api_key    = new ConcealedString((string) ($body['api_key'] ?? ''));
        $index_name = (string) ($body['index_name'] ?? '');

        try {
            $this->server_url_validator->checkIsValid($server_url);
            $this->api_key_validator->checkIsValid($api_key);
            $this->index_name_validator->checkIsValid($index_name);
        } catch (InvalidConfigKeyValueException $exception) {
            throw new ForbiddenException();
        }

        $this->config_set->set(RemoteMeilisearchServerSettings::URL, $server_url);
        $this->config_set->set(RemoteMeilisearchServerSettings::API_KEY, $api_key);
        $this->config_set->set(RemoteMeilisearchServerSettings::INDEX_NAME, $index_name);

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            MeilisearchAdminSettingsController::ADMIN_SETTINGS_URL,
            new NewFeedback(\Feedback::INFO, dgettext('tuleap-fts_meilisearch', 'Meilisearch server settings have been saved')),
        );
    }
}
