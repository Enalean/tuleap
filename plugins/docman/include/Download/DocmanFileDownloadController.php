<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Download;

use Docman_File;
use Docman_ItemFactory;
use Psr\Log\LoggerInterface;
use PFUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class DocmanFileDownloadController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var DocmanFileDownloadResponseGenerator
     */
    private $file_download_response_generator;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EmitterInterface $emitter,
        Docman_ItemFactory $item_factory,
        DocmanFileDownloadResponseGenerator $file_download_response_generator,
        LoggerInterface $logger,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct(
            $emitter,
            ...$middleware_stack
        );
        $this->item_factory                     = $item_factory;
        $this->file_download_response_generator = $file_download_response_generator;
        $this->logger                           = $logger;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $item = $this->item_factory->getItemFromDb($request->getAttribute('file_id'));

        if ($item === null || ! $item instanceof Docman_File) {
            throw new NotFoundException(dgettext('tuleap-docman', 'The file cannot be found.'));
        }
        try {
            $attribute_version_id = $request->getAttribute('version_id');
            $current_user         = $request->getAttribute(RESTCurrentUserMiddleware::class);
            assert($current_user instanceof PFUser);
            return $this->file_download_response_generator->generateResponse(
                $request,
                $current_user,
                $item,
                $attribute_version_id !== null ? (int) $attribute_version_id : null
            );
        } catch (VersionNotFoundException $exception) {
            $this->logger->debug($exception->getMessage());
            throw new NotFoundException(dgettext('tuleap-docman', 'The requested version of this file cannot be found.'));
        } catch (FileDownloadException $exception) {
            $this->logger->debug($exception->getMessage());
            throw new NotFoundException(dgettext('tuleap-docman', 'The file cannot be found.'));
        }
    }
}
