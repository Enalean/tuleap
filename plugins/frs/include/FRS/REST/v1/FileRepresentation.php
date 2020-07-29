<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\FRS\REST\v1;

use Tuleap\REST\JsonCast;
use FRSFile;
use UserManager;
use FRSProcessorDao;
use FRSFileTypeDao;
use Tuleap\Dao\FRSFileDownloadDao;
use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-immutable
 */
class FileRepresentation
{
    public const ROUTE = 'frs_files';

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $uri;

    /**
     * @var string {@type string}
     */
    public $name;

    /**
     * @var string {@type string}
     */
    public $download_url;

    /**
     * @var int {@type int}
     */
    public $file_size;

    /**
     * @var int {@type int}
     */
    public $nb_download;

    /**
     * @var string | null {@type string}
     */
    public $arch;

    /**
     * @var string | null {@type string}
     */
    public $type;

    /**
     * @var string {@type date}
     */
    public $date;

    /**
     * @var string {@type string}
     */
    public $reference_md5;

    /**
     * @var string {@type string}
     */
    public $computed_md5;

    /**
     * @var UserRepresentation {@type UserRepresentation}
     */
    public $owner;

    public function __construct(FRSFile $file)
    {
        $this->id            = JsonCast::toInt($file->getFileID());
        $this->uri           = self::ROUTE . '/' . $this->id;
        $this->name          = self::retrieveOnlyFileName($file);
        $this->download_url  = '/file/download/' . urlencode($this->id);
        $this->file_size     = self::retrieveFileSize($file);
        $this->nb_download   = self::getDownloads($file);
        $this->arch          = self::retrieveProcessorLabel($file);
        $this->type          = self::retrieveTypeLabel($file);
        $this->date          = JsonCast::toDate($file->getPostDate());
        $this->reference_md5 = $file->getReferenceMd5();
        $this->computed_md5  = $file->getComputedMd5();
        $this->owner         = self::getUser($file);
    }

    private static function retrieveOnlyFileName(FRSFile $file): string
    {
        if (preg_match("/^.+\/(.+)$/", $file->getFileName(), $matches)) {
            return $matches[1];
        }

        return "";
    }

    private static function getUser(FRSFile $file): UserRepresentation
    {
        $owner = UserManager::instance()->getUserById($file->getUserId());
        return UserRepresentation::build($owner);
    }

    private static function getDownloads(FRSFile $file)
    {
        $download_dao = new FRSFileDownloadDao();
        $downloads    = $download_dao->searchByFile($file->getFileID());

        return ($downloads['downloads'] ? $downloads['downloads'] : 0);
    }

    private static function retrieveFileSize(FRSFile $file): int
    {
        return $file->getFileSize();
    }

    private static function retrieveProcessorLabel(FRSFile $file): ?string
    {
        $processor_dao = new FRSProcessorDao();
        $processor     = $processor_dao->searchById($file->getProcessorID());

        return $processor["name"];
    }

    private static function retrieveTypeLabel(FRSFile $file): ?string
    {
        $type_dao = new FRSFileTypeDao();
        $type     = $type_dao->searchById($file->getTypeID());

        return $type["name"];
    }
}
