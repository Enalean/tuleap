<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open\Editor;

use DateTimeImmutable;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerKeyEncryption;
use Tuleap\OnlyOffice\Save\OnlyOfficeSaveCallbackURLGenerator;

final class OnlyOfficeGlobalEditorJWTokenProvider implements ProvideOnlyOfficeGlobalEditorJWToken
{
    public function __construct(
        private ProvideOnlyOfficeConfigDocument $config_document_provider,
        private OnlyOfficeSaveCallbackURLGenerator $office_save_callback_url_generator,
        private JwtFacade $jwt_facade,
        private Signer $signer,
        private DocumentServerKeyEncryption $encryption,
    ) {
    }

    /**
     * @psalm-return Ok<non-empty-string>|Err<Fault>
     */
    public function getGlobalEditorJWToken(\PFUser $user, int $item_id, DateTimeImmutable $now): Ok|Err
    {
        return $this->config_document_provider->getDocumentConfig($user, $item_id, $now)
            ->andThen(
                /** @psalm-return Ok<non-empty-string> */
                function (OnlyOfficeDocumentConfig $document_config) use ($user, $now): Ok {
                    $callback_url = $this->office_save_callback_url_generator->getCallbackURL($user, $document_config, $now);
                    $signing_key  = $this->encryption->decryptValue($document_config->getAssociatedDocument()->document_server->encrypted_secret_key->getString());
                    $jwt          = $this->jwt_facade->issue(
                        $this->signer,
                        Signer\Key\InMemory::plainText($signing_key->getString()),
                        static fn (
                            Builder $builder,
                            DateTimeImmutable $issued_at,
                        ): Builder => $builder
                            ->expiresAt($issued_at->modify('+ 30 seconds'))
                            ->withClaim('document', $document_config)
                            ->withClaim('editorConfig', OnlyOfficeEditorConfig::fromUser($user, $callback_url))
                    );

                    return Result::ok($jwt->toString());
                }
            );
    }
}
