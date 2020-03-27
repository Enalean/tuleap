<?php
/**
 * Copyright (c) Enalean, 2014 — Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Luracast\Restler\RestException;
use Tracker_FileInfo;
use Tracker_FileInfo_InvalidFileInfoException;
use Tracker_FileInfo_UnauthorisedException;
use Tuleap\Tracker\REST\Artifact\FileDataRepresentation;
use Tuleap\REST\Exceptions\LimitOutOfBoundsException;
use Tuleap\REST\Header;
use UserManager;
use PFUser;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_FileInfoFactory;
use Tracker_FileInfoDao;

class ArtifactFilesResource
{
    private const DEFAULT_LIMIT = 1048576; // 1Mo

    /** @var PFUser */
    private $user;

    public function __construct()
    {
        $this->user          = UserManager::instance()->getCurrentUser();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id)
    {
        $this->sendAllowHeadersForArtifactFilesId();
    }

    /**
     * Get a chunk of a file
     *
     * A user can only access the attached files they can view.
     *
     * @url GET {id}
     * @oauth2-scope read:tracker
     * @param int $id     Id of the file
     * @param int $offset Where to start to read the file
     * @param int $limit  How much to read the file
     *
     *
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 406
     */
    protected function getId($id, $offset = 0, $limit = self::DEFAULT_LIMIT) : FileDataRepresentation
    {
        $this->checkLimitValue($limit);

        $file_info = $this->getAttachedFileInfo($id);
        $size      = $file_info->getFilesize();

        $this->sendAllowHeadersForArtifactFilesId();
        $this->sendPaginationHeaders($limit, $offset, $size);

        $file_data_representation = new FileDataRepresentation();

        return $file_data_representation->build($file_info->getContent($offset, $limit));
    }

    /**
     * @throws RestException 404
     */
    private function getAttachedFileInfo(int $id) : Tracker_FileInfo
    {
        $file_info_factory = new Tracker_FileInfoFactory(
            new Tracker_FileInfoDao(),
            Tracker_FormElementFactory::instance(),
            Tracker_ArtifactFactory::instance()
        );

        $file_info = $file_info_factory->getById($id);
        if ($file_info === null) {
            throw new RestException(404);
        }

        try {
            $file_info_factory->getArtifactByFileInfoIdAndUser($this->user, $file_info->getId());
        } catch (Tracker_FileInfo_InvalidFileInfoException | Tracker_FileInfo_UnauthorisedException $exception) {
            throw new RestException(404);
        }

        if (! $file_info->getField()->userCanRead($this->user)) {
            throw new RestException(404);
        }

        return $file_info;
    }

    /**
     * @throws RestException 406
     */
    private function checkLimitValue($limit)
    {
        if ($limit > self::DEFAULT_LIMIT) {
            throw new LimitOutOfBoundsException(self::DEFAULT_LIMIT);
        }
    }

    private function sendAllowHeadersForArtifactFilesId()
    {
        Header::allowOptionsGet();
        Header::sendMaxFileChunkSizeHeaders(self::DEFAULT_LIMIT);
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::DEFAULT_LIMIT);
    }
}
