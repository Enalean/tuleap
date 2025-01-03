<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright 2005, 2006, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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

use GuzzleHttp\Psr7\ServerRequest;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

/**
 *
 *<pre>
 * `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
 * `attachment_id` INT( 11 ) NOT NULL ,
 * `user_id` INT( 11 ) NOT NULL ,
 * `date` INT( 11 ) NOT NULL ,
 * `revision` INT( 11 ) NOT NULL ,
 * `mimetype` VARCHAR( 255 ) NOT NULL ,
 * `size` INT( 11 ) NOT NULL
 * PRIMARY KEY ( `id` )
 *</pre>
 *
 *
 * @see       WikiAttachment
 */
class WikiAttachmentRevision
{
    public ?int $id           = null;
    public ?int $attachmentId = null;
    public ?int $owner_id     = null;

    public ?int $date       = null;
    public ?int $revision   = null;
    public string $mimeType = '';
    public ?int $size       = null;


    public ?string $filename = null;
    public ?int $gid         = null;
    public ?string $basedir  = null;
    /**
     * @var string
     */
    private $displayFilename;


    public function __construct($gid = null)
    {
        if (is_numeric($gid)) {
            $this->gid     = (int) $gid;
            $this->basedir = ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->gid;
        }
    }

    public static function &getDao()
    {
        static $_codendi_wikiattachmentrevisiondao_instance;

        if (! $_codendi_wikiattachmentrevisiondao_instance) {
            $_codendi_wikiattachmentrevisiondao_instance = new WikiAttachmentRevisionDao(CodendiDataAccess::instance());
        }

        return $_codendi_wikiattachmentrevisiondao_instance;
    }

    public function dbFetch()
    {
        $dao = self::getDao();
        $dar = $dao->getRevision($this->attachmentId, $this->revision);

        if ($dar->rowCount() > 1) {
            trigger_error(
                $GLOBALS['Language']->getText(
                    'wiki_lib_attachment_rev',
                    'err_multi_id',
                    [ForgeConfig::get('sys_email_admin'),
                        $this->attachmentId,
                        $this->revision,
                        ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME),
                    ]
                ),
                E_USER_ERROR
            );
        } else {
            $this->setFromRow($dar->getRow());
        }
    }

    public function create($userfile_tmpname)
    {
        $this->getFilename();
        $file_dir = $this->basedir . '/' . $this->filename;

        /** @todo: add lock */

        $waIter         = self::getRevisionIterator($this->gid ?? 0, $this->attachmentId ?? 0);
        $this->revision = $waIter->count();

        if (! move_uploaded_file($userfile_tmpname, $file_dir . '/' . $this->revision)) {
            trigger_error(
                $GLOBALS['Language']->getText(
                    'wiki_lib_attachment_rev',
                    'err_upl_mv',
                    [$this->filename]
                ),
                E_USER_ERROR
            );
        }

        chmod($file_dir . '/' . $this->revision, 0600);

        $ret = $this->dbadd();

        /** @todo: add unlock */

        return $ret;
    }

    public function dbadd()
    {
        $dao = self::getDao();
        $res = $dao->create(
            $this->attachmentId,
            $this->owner_id,
            $this->date,
            $this->revision,
            $this->mimeType,
            $this->size
        );

        if ($res === false) {
            trigger_error(
                $GLOBALS['Language']->getText(
                    'wiki_lib_attachment_rev',
                    'err_create'
                ),
                E_USER_ERROR
            );
        } else {
            return true;
        }
    }

    public function htmlDump()
    {
        if ($this->exist()) {
            $response_builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());
            $response         = $response_builder->fromFilePath(
                ServerRequest::fromGlobals(),
                $this->getFilePath(),
                $this->getDisplayFilename(),
                $this->getMimeType()
            );
            (new SapiStreamEmitter())->emit($response);
            exit();
        }
    }

    /**
     * @return string
     */
    private function getFilePath()
    {
        $this->getFilename();

        return $this->basedir . '/' . $this->filename . '/' . $this->revision;
    }

    public function exist()
    {
        $this->getFilename();

        return is_file($this->basedir . '/' . $this->filename . '/' . $this->revision);
    }

    public function log($userId)
    {
        $dao = self::getDao();
        $dao->log(
            $this->attachmentId,
            $this->id,
            $this->gid,
            $userId,
            time()
        );
    }

    public function setFromRow($row)
    {
        $this->id           = $row['id'];
        $this->attachmentId = $row['attachment_id'];
        $this->owner_id     = $row['user_id'];
        $this->date         = $row['date'];
        $this->revision     = $row['revision'];
        $this->mimeType     = $row['mimetype'];
        $this->size         = $row['size'];
    }

    public function setFilename($name = '')
    {
        $this->filename = $name;
        return true;
    }

    public function setGid($gid)
    {
        if (is_numeric($gid)) {
            $this->gid     = (int) $gid;
            $this->basedir = ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->gid;
        }
    }

    public function setSize($s)
    {
        $sys_max_size_upload = (int) ForgeConfig::get('sys_max_size_upload');

        if ($s > $sys_max_size_upload) {
            trigger_error(
                $GLOBALS['Language']->getText(
                    'wiki_lib_attachment_rev',
                    'err_too_big'
                ),
                E_USER_ERROR
            );
        }

        $this->size = (int) $s;
        return true;
    }

    public function setMimeType($m)
    {
        $this->mimeType =  $m;
        return true;
    }

    public function setOwnerId($uid)
    {
        $this->owner_id = (int) $uid;
        return true;
    }

    public function setAttachmentId($aid)
    {
        $this->attachmentId = (int) $aid;
        return true;
    }

    public function setDate($date)
    {
        $this->date = (int) $date;
        return true;
    }

    public function setRevision($rev)
    {
        $this->revision = (int) $rev;
        return true;
    }

    public function getRevision()
    {
        return $this->revision;
    }

    public function getFilename()
    {
        if (empty($this->filename)) {
            $wa = new WikiAttachment();
            // @todo: catch error when wiki no attachementId is set.
            $wa->initWithId($this->attachmentId);
            // @todo: catch error when given attchId do not exist
            $this->displayFilename = $wa->getFilename();
            $this->filename        = $wa->getFilesystemName();
        }
        return $this->filename;
    }

    private function getDisplayFilename(): string
    {
        $this->getFilename();
        return $this->displayFilename;
    }

    public function getOwnerId()
    {
        return $this->owner_id;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getMimeType()
    {
        return trim($this->mimeType, "'");
    }

    public function getDate()
    {
        return $this->date;
    }

    /**
     * @access public static
     */
    public static function getRevisionIterator(int $gid, int $id)
    {
        $warArray = [];

        $dao = self::getDao();
        $dar = $dao->getAllRevisions($id);
        while ($row = $dar->getRow()) {
            $war = new WikiAttachmentRevision($gid);
            $war->setFromRow($row);
            $warArray[] = $war;
            unset($war);
        }

        $ai = new ArrayIterator($warArray);
        return $ai;
    }
}
