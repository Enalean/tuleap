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
    public $id;
    public $attachmentId;
    public $owner_id;

    public $date;
    public $revision;
    public $mimeType;
    public $size;


    public $file;
    public $gid;
    public $basedir;
    /**
     * @var string
     */
    private $displayFilename;


    public function __construct($gid = null)
    {
        if (is_numeric($gid)) {
            $this->gid = (int) $gid;
            $this->basedir = $GLOBALS['sys_wiki_attachment_data_dir'] . '/' . $this->gid;
        }
    }

    public function &getDao()
    {
        static $_codendi_wikiattachmentrevisiondao_instance;

        if (!$_codendi_wikiattachmentrevisiondao_instance) {
            $_codendi_wikiattachmentrevisiondao_instance = new WikiAttachmentRevisionDao(CodendiDataAccess::instance());
        }

        return $_codendi_wikiattachmentrevisiondao_instance;
    }

    public function dbFetch()
    {
        $dao = $this->getDao();
        $dar = $dao->getRevision($this->attachmentId, $this->revision);

        if ($dar->rowCount() > 1) {
            trigger_error(
                $GLOBALS['Language']->getText(
                    'wiki_lib_attachment_rev',
                    'err_multi_id',
                    array($GLOBALS['sys_email_admin'],
                                                              $this->attachmentId,
                                                              $this->revision,
                    $GLOBALS['sys_fullname'])
                ),
                E_USER_ERROR
            );
            return false;
        } else {
            $this->setFromRow($dar->getRow());
        }
    }

    public function create($userfile_tmpname)
    {
        $this->getFilename();
        $file_dir = $this->basedir . '/' . $this->filename;

        /** @todo: add lock */

        $waIter = $this->getRevisionIterator();
        $this->revision = $waIter->count();

        if (!move_uploaded_file($userfile_tmpname, $file_dir . '/' . $this->revision)) {
            trigger_error(
                $GLOBALS['Language']->getText(
                    'wiki_lib_attachment_rev',
                    'err_upl_mv',
                    array($this->filename)
                ),
                E_USER_ERROR
            );
            return false;
        }

        chmod($file_dir . '/' . $this->revision, 0600);

        $ret = $this->dbadd();

        /** @todo: add unlock */

        return $ret;
    }

    public function dbadd()
    {
        $dao = $this->getDao();
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
            return false;
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
        $dao = $this->getDao();
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

    public function setFilename($name = "")
    {
        $this->filename = $name;
        return true;
    }

    public function setGid($gid)
    {
        if (is_numeric($gid)) {
            $this->gid = (int) $gid;
            $this->basedir = $GLOBALS['sys_wiki_attachment_data_dir'] . '/' . $this->gid;
        }
    }

    public function setSize($s)
    {
        global $sys_max_size_upload;

        if ($s > $sys_max_size_upload) {
            trigger_error(
                $GLOBALS['Language']->getText(
                    'wiki_lib_attachment_rev',
                    'err_too_big'
                ),
                E_USER_ERROR
            );
            return false;
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
            $this->filename       = $wa->getFilesystemName();
        }
        return $this->filename;
    }

    private function getDisplayFilename() : string
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
    public function getRevisionIterator($gid = null, $id = null)
    {
        $warArray = array();
        if ($id !== null) {
            $id  = (int) $id;
            $gid = (int) $gid;
        } else {
            $gid = $this->gid;
            $id  = $this->attachmentId;
        }

        $dao = WikiAttachmentRevision::getDao();
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
