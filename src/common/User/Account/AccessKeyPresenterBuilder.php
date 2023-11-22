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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account;

use ForgeConfig;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Date\DateHelper;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyMetadataPresenter;
use Tuleap\User\AccessKey\AccessKeyMetadataRetriever;
use Tuleap\User\AccessKey\LastAccessKeyIdentifierStore;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\User\AccessKey\PrefixAccessKey;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeBuilderCollector;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;
use Tuleap\User\AccessKey\Scope\AccessKeyScopePresenter;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;
use Tuleap\User\AccessKey\Scope\CoreAccessKeyScopeBuilderFactory;

class AccessKeyPresenterBuilder
{
    /**
     * @var AuthenticationScopeBuilder
     */
    private $access_key_scope_builder;
    /**
     * @var AccessKeyMetadataRetriever
     */
    private $access_key_metadata_retriever;
    /**
     * @var SplitTokenFormatter
     */
    private $split_token_formatter;
    /**
     * @var EncryptionKey
     */
    private $encryption_key;

    public function __construct(
        AuthenticationScopeBuilder $access_key_scope_builder,
        AccessKeyMetadataRetriever $access_key_metadata_retriever,
        SplitTokenFormatter $split_token_formatter,
        EncryptionKey $encryption_key,
    ) {
        $this->access_key_scope_builder      = $access_key_scope_builder;
        $this->access_key_metadata_retriever = $access_key_metadata_retriever;
        $this->split_token_formatter         = $split_token_formatter;
        $this->encryption_key                = $encryption_key;
    }

    /**
     * @throws \Tuleap\Cryptography\Exception\CannotPerformIOOperationException
     */
    public static function build(): self
    {
        $access_key_scope_builder = AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreAccessKeyScopeBuilderFactory::buildCoreAccessKeyScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new AccessKeyScopeBuilderCollector())
        );
        return new self(
            $access_key_scope_builder,
            new AccessKeyMetadataRetriever(
                new AccessKeyDAO(),
                new AccessKeyScopeRetriever(
                    new AccessKeyScopeDAO(),
                    $access_key_scope_builder
                )
            ),
            new PrefixedSplitTokenSerializer(new PrefixAccessKey()),
            (new KeyFactory())->getEncryptionKey(),
        );
    }

    /**
     * @throws \Tuleap\Cryptography\Exception\InvalidCiphertextException
     */
    public function getForUser(\PFUser $user, array &$storage): AccessKeyPresenter
    {
        $access_key_scope_presenters = [];
        foreach ($this->access_key_scope_builder->buildAllAvailableAuthenticationScopes() as $access_key_scope) {
            $access_key_scope_presenters[] = new AccessKeyScopePresenter($access_key_scope);
        }
        $access_key_presenters = [];
        foreach ($this->access_key_metadata_retriever->getMetadataByUser($user) as $access_key_metadata) {
            $access_key_presenters[] = new AccessKeyMetadataPresenter($access_key_metadata);
        }

        $last_access_key_identifier_store = new LastAccessKeyIdentifierStore(
            $this->split_token_formatter,
            $this->encryption_key,
            $storage
        );
        return new AccessKeyPresenter(
            $access_key_scope_presenters,
            $access_key_presenters,
            $last_access_key_identifier_store->getLastGeneratedAccessKeyIdentifier(),
            DateHelper::distanceOfTimeInWords(0, ForgeConfig::get('last_access_resolution'))
        );
    }
}
