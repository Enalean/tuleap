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

class FileRepresentation
{
    public const ROUTE = 'frs_files';

    /**
     * @var id {@type int}
     */
    public $id;

    /**
     * @var uri {@type string}
     */
    public $uri;

    /**
     * @var $name {@type string}
     */
    public $name;

    /**
     * @var $download_url {@type string}
     */
    public $download_url;

    /**
     * @var $file_size {@type int}
     */
    public $file_size;

    /**
     * @var $nb_download {@type int}
     */
    public $nb_download;

    /**
     * @var $arch {@type string}
     */
    public $arch;

    /**
     * @var $type {@type string}
     */
    public $type;

    /**
     * @var $date {@type date}
     */
    public $date;

    /**
     * @var $reference_md5 {@type string}
     */
    public $reference_md5;

    /**
     * @var $computed_md5 {@type string}
     */
    public $computed_md5;

    /**
     * @var $owner {@type UserRepresentation}
     */
    public $owner;

    public function build(FRSFile $file)
    {
        $this->id            = JsonCast::toInt($file->getFileID());
        $this->uri           = self::ROUTE . '/' . $this->id;
        $this->name          = $this->retrieveOnlyFileName($file);
        $this->download_url  = '/file/download/' . urlencode($this->id);
        $this->file_size     = JsonCast::toInt($file->getFileSize());
        $this->nb_download   = $this->getDownloads($file);
        $this->arch          = $this->retrieveProcessorLabel($file);
        $this->type          = $this->retrieveTypeLabel($file);
        $this->date          = JsonCast::toDate($file->getPostDate());
        $this->reference_md5 = $file->getReferenceMd5();
        $this->computed_md5  = $file->getComputedMd5();
        $this->owner         = $this->getUser($file);
    }

    private function retrieveOnlyFileName(FRSFile $file)
    {
        if (preg_match("/^.+\/(.+)$/", $file->getFileName(), $matches)) {
            return $matches[1];
        }

        return "";
    }

    private function getUser(FRSFile $file)
    {
        $owner = UserManager::instance()->getUserById($file->getUserId());
        $user_representation = new UserRepresentation();
        return $user_representation->build($owner);
    }

    private function getDownloads(FRSFile $file)
    {
        $download_dao = new FRSFileDownloadDao();
        $downloads    = $download_dao->searchByFile($file->getFileID());

        return ($downloads['downloads'] ? $downloads['downloads'] : 0);
    }

    private function retrieveProcessorLabel(FRSFile $file)
    {
        $processor_dao = new FRSProcessorDao();
        $processor     = $processor_dao->searchById($file->getProcessorID());

        return $processor["name"];
    }

    private function retrieveTypeLabel(FRSFile $file)
    {
        $type_dao = new FRSFileTypeDao();
        $type     = $type_dao->searchById($file->getTypeID());

        return $type["name"];
    }
}
