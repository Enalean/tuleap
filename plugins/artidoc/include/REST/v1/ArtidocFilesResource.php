<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use Docman_ItemFactory;
use ForgeConfig;
use Luracast\Restler\RestException;
use Tuleap\Artidoc\Adapter\Document\ArtidocRetriever;
use Tuleap\Artidoc\Adapter\Document\ArtidocWithContextDecorator;
use Tuleap\Artidoc\Adapter\Document\SearchArtidocDocumentDao;
use Tuleap\Artidoc\ArtidocWithContextRetrieverBuilder;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\Artidoc\Upload\Section\File\CannotWriteFileFault;
use Tuleap\Artidoc\Upload\Section\File\EmptyFileToUploadFinisher;
use Tuleap\Artidoc\Upload\Section\File\FileToUploadCreator;
use Tuleap\Artidoc\Upload\Section\File\OngoingUploadDao;
use Tuleap\Artidoc\Upload\Section\File\UploadCreationConflictFault;
use Tuleap\Artidoc\Upload\Section\File\UploadMaxSizeExceededFault;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\RESTLogger;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;
use UserManager;

final class ArtidocFilesResource extends AuthenticatedResource
{
    public const string ROUTE = 'artidoc_files';

    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create file
     *
     * Create a file in an artidoc so that it can be attached to a freetext section later.
     *
     * @url POST
     *
     * @access protected
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function post(FilePOSTRepresentation $payload): CreatedFileRepresentation
    {
        $this->checkAccess();
        $user = UserManager::instance()->getCurrentUser();

        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $retriever_builder = new ArtidocWithContextRetrieverBuilder(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );
        $retriever         = $retriever_builder->buildForUser($user);

        $ongoing_upload_dao = new OngoingUploadDao(new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()));
        $file_creator       = new FileCreator(
            new FileToUploadCreator(
                $ongoing_upload_dao,
                $ongoing_upload_dao,
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                (int) ForgeConfig::get('sys_max_size_upload')
            ),
            new EmptyFileToUploadFinisher()
        );

        return $retriever->retrieveArtidocUserCanWrite($payload->artidoc_id)
            ->andThen(static fn (ArtidocWithContext $artidoc) => $file_creator->create($artidoc->document, $user, $payload, new \DateTimeImmutable()))
            ->match(
                static fn (CreatedFileRepresentation $representation) => $representation,
                $this->handleFaultInPost(...),
            );
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    private function handleFaultInPost(Fault $fault): never
    {
        if ($fault instanceof CannotWriteFileFault) {
            Fault::writeToLogger($fault, RESTLogger::getLogger());
            throw new RestException(500, (string) $fault);
        }
        throw match ($fault::class) {
            UploadCreationConflictFault::class => new I18NRestException(
                409,
                dgettext('tuleap-artidoc', 'This file is already being uploaded.')
            ),
            UploadMaxSizeExceededFault::class => new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-artidoc',
                        'The maximum allowed size for a file is %1$s bytes, you requested the creation of a file of %2$s bytes.'
                    ),
                    $fault->max_allowed_size,
                    $fault->requested_size,
                )
            ),
            UserCannotWriteDocumentFault::class => new I18NRestException(
                403,
                dgettext('tuleap-artidoc', "You don't have permission to write the document.")
            ),
            default => new RestException(404)
        };
    }
}
