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
use Docman_PermissionsManager;
use Docman_VersionFactory;
use PFUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\Response\BinaryFileResponseBuilder;

class DocmanFileDownloadResponseGenerator
{
    /**
     * @var Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var BinaryFileResponseBuilder
     */
    private $binary_file_response_builder;

    public function __construct(
        Docman_VersionFactory $version_factory,
        BinaryFileResponseBuilder $binary_file_response_builder
    ) {
        $this->version_factory              = $version_factory;
        $this->binary_file_response_builder = $binary_file_response_builder;
    }

    /**
     * @throws UserCannotAccessFileException
     * @throws VersionNotFoundException
     * @throws FileDoesNotExistException
     */
    public function generateResponse(
        ServerRequestInterface $request,
        PFUser $current_user,
        Docman_File $docman_file,
        ?int $version_id
    ): ResponseInterface {
        $permission_manager = Docman_PermissionsManager::instance($docman_file->getGroupId());
        if (! $permission_manager->userCanAccess($current_user, $docman_file->getId())) {
            throw new UserCannotAccessFileException($current_user, $docman_file);
        }

        if ($version_id !== null) {
            $version = $this->version_factory->getSpecificVersion($docman_file, $version_id);
        } else {
            $version = $docman_file->getCurrentVersion();
        }

        if ($version === null) {
            throw new VersionNotFoundException($docman_file, $version_id);
        }

        $file_path = $version->getPath();
        if (! file_exists($file_path)) {
            throw new FileDoesNotExistException($version);
        }

        $version->preDownload($docman_file, $current_user);

        return $this->binary_file_response_builder->fromFilePath(
            $request,
            $file_path,
            $version->getFilename(),
            $version->getFiletype()
        );
    }
}
