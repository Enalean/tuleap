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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS;

use FRSFileFactory;
use PFUser;
use Project_AccessException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\REST\RESTCurrentUserMiddleware;
use URLVerification;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class FRSFileDownloadController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var FRSFileFactory
     */
    private $file_factory;
    /**
     * @var BinaryFileResponseBuilder
     */
    private $response_builder;
    /**
     * @var URLVerification
     */
    private $url_verification;

    public function __construct(
        URLVerification $url_verification,
        FRSFileFactory $file_factory,
        BinaryFileResponseBuilder $response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->url_verification = $url_verification;
        $this->file_factory     = $file_factory;
        $this->response_builder = $response_builder;
    }

    /**
     * @throws NotFoundException
     * @throws FRSFileNotPresentInStorage
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $file_id = (int) $request->getAttribute('file_id');

        $file  = $this->file_factory->getFRSFileFromDb($file_id);

        if ($file === null) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        $current_user = $request->getAttribute(RESTCurrentUserMiddleware::class);
        \assert($current_user instanceof PFUser);

        try {
            $this->url_verification->userCanAccessProject($current_user, $file->getGroup());
        } catch (Project_AccessException $e) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        // Check permissions for downloading the file, and check that the file has the active status
        if (! $file->userCanDownload($current_user) || ! $file->isActive()) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        if (! $file->fileExists()) {
            throw new FRSFileNotPresentInStorage($file);
        }

        // Log the download in the Log system
        $file->LogDownload($current_user->getId());

        return $this->response_builder->fromFilePath($request, $file->getFileLocation(), basename($file->getFileName()));
    }
}
