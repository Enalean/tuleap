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

namespace Tuleap\OAuth2Server\App;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

class LastCreatedOAuth2AppStore
{
    private const STORAGE_NAME = 'oauth2_last_created_app';

    /**
     * @var EncryptionKey
     */
    private $encryption_key;
    /**
     * @var array
     */
    private $storage;
    /**
     * @var SplitTokenFormatter
     */
    private $split_token_formatter;

    public function __construct(
        SplitTokenFormatter $split_token_formatter,
        EncryptionKey $encryption_key,
        array &$storage
    ) {
        $this->split_token_formatter = $split_token_formatter;
        $this->encryption_key        = $encryption_key;
        $this->storage               =& $storage;
    }

    public function storeLastCreatedApp(int $app_id, NewOAuth2App $new_oauth2_app): void
    {
        $this->storage[self::STORAGE_NAME] = [
            'app_id'   => $app_id,
            'verifier' => SymmetricCrypto::encrypt(
                $new_oauth2_app->getSecret()->getString(),
                $this->encryption_key
            )
        ];
    }

    public function getLastCreatedApp(): ?LastCreatedOAuth2App
    {
        if (! isset($this->storage[self::STORAGE_NAME])) {
            return null;
        }
        $storage_value = $this->storage[self::STORAGE_NAME];
        unset($this->storage[self::STORAGE_NAME]);

        $app_id = $storage_value['app_id'];
        return new LastCreatedOAuth2App(
            $app_id,
            $this->split_token_formatter->getIdentifier(
                new SplitToken(
                    $app_id,
                    new SplitTokenVerificationString(
                        SymmetricCrypto::decrypt($storage_value['verifier'], $this->encryption_key)
                    )
                )
            )
        );
    }
}
