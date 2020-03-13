<?php
// Copyright (c) Enalean, 2016-2018. All Rights Reserved.
// Copyright (c) STMicroelectronics, 2005. All Rights Reserved.

 // Originally written by Jean-Philippe Giola, 2005
 //
 // This file is a part of Tuleap.
 //
 // Tuleap is free software; you can redistribute it and/or modify
 // it under the terms of the GNU General Public License as published by
 // the Free Software Foundation; either version 2 of the License, or
 // (at your option) any later version.
 //
 // Tuleap is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU General Public License for more details.
 //
 // You should have received a copy of the GNU General Public License
 // along with Tuleap; if not, write to the Free Software
 // Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 //
 // $Id$
namespace Tuleap\ForumML;

use Tuleap\ForumML\Incoming\IncomingMail;

class MessageArchiver
{
    private $id_list;

    // Class Constructor
    public function __construct($list_id)
    {
        // set id_list
        $this->id_list = $list_id;
    }

    // Insert values into forumml_messageheader table
    private function insertMessageHeader($id_message, $id_header, $value)
    {
        $qry = sprintf(
            'INSERT INTO plugin_forumml_messageheader' .
                        ' (id_message, id_header, value)' .
                        ' VALUES (%d,%d,"%s")',
            db_ei($id_message),
            db_ei($id_header),
            db_es($value)
        );
        db_query($qry);
    }

    // Insert values into forumml_attachment table
    protected function insertAttachment($id_message, $filename, $filetype, $filepath, $content_id = "")
    {
        if (is_file($filepath)) {
            $filesize = filesize($filepath);
        } else {
            $filesize = 0;
        }
        $qry = sprintf(
            'INSERT INTO plugin_forumml_attachment' .
                       ' (id_message, file_name, file_type, file_size, file_path, content_id)' .
                       ' VALUES (%d,"%s","%s",%d, "%s", "%s")',
            db_ei($id_message),
            db_es($filename),
            db_es($filetype),
            db_ei($filesize),
            db_es($filepath),
            db_es($content_id)
        );
        db_query($qry);
    }

    // Insert values into forumml_header table
    private function insertHeader($header)
    {
        // Search if the header is already in the table
        $qry = sprintf(
            'SELECT id_header' .
                        ' FROM plugin_forumml_header' .
                        ' WHERE name = "%s"',
            db_es($header)
        );
        $result = db_query($qry);

        // If not, insert it
        if (db_result($result, 0, 'id_header') == "") {
            $sql = sprintf(
                'INSERT INTO plugin_forumml_header' .
                            ' (id_header, name)' .
                            ' VALUES (%d, "%s")',
                "",
                db_es($header)
            );
            $res = db_query($sql);
            return (db_insertid($res));
        } else {
            return (db_result($result, 0, 'id_header'));
        }
    }

    private function getParentMessageFromHeader($messageIdHeader)
    {
        $qry = 'SELECT id_message' .
            ' FROM plugin_forumml_messageheader' .
            ' WHERE id_header = 1' .
            ' AND value = "' . db_es($messageIdHeader) . '"';
        $result = db_query($qry);
        if ($result && !db_error()) {
            $row = db_fetch_array($result);
            return $row['id_message'];
        }
        return false;
    }

    private function updateParentDate($messageId, $date)
    {
        if ($messageId != 0) {
            $sql = 'SELECT id_parent, last_thread_update FROM plugin_forumml_message WHERE id_message = ' . db_ei($messageId);
            $dar = db_query($sql);
            if ($dar && !db_error()) {
                $row = db_fetch_array($dar);
                if ($date > $row['last_thread_update']) {
                    $sql = 'UPDATE plugin_forumml_message' .
                        ' SET last_thread_update = ' . db_ei($date) .
                        ' WHERE id_message=' . db_ei($messageId);
                    db_query($sql);

                    $this->updateParentDate($row['id_parent'], $date);
                }
            }
        }
    }

    // Insert values into forumml_message table
    protected function insertMessage(IncomingMail $incoming_mail)
    {
        $headers = $incoming_mail->getHeaders();

        if (isset($headers["in-reply-to"])) {
            // special case: 'in-reply-to' header may contain "Message from ... "
            if (preg_match('/^Message from.*$/', $headers["in-reply-to"])) {
                $arr = explode(" ", $headers["in-reply-to"]);
                $reply_to = $arr[count($headers["in-reply-to"]) - 1];
            } else {
                $reply_to = $headers["in-reply-to"];
            }
        } else {
            if (isset($headers["references"])) {
                // special case: 'in-reply-to' header is not set, but 'references' - which contain list of parent messages ids - is set
                $ref_arr = explode(" ", $headers["references"]);
                $reply_to = $ref_arr[count($headers["references"]) - 1];
            } else {
                $reply_to = "";
            }
        }

        // Message date
        // Cannot rely on server's date because it might be different
        // and it doesn't work when it comes to load mail archives!
        $messageDate = strtotime($headers['date']);

        $id_parent = 0;
        // If the current message is an answer
        if ($reply_to != "") {
            $id_parent = $this->getParentMessageFromHeader($reply_to);
        }

        if ($id_parent != 0) {
            $this->updateParentDate($id_parent, $messageDate);
        }

        $body = $incoming_mail->getBody();
        $sql  = sprintf(
            'INSERT INTO plugin_forumml_message' .
                        ' (id_message, id_list, id_parent, body, last_thread_update, msg_type)' .
                        ' VALUES (%d, %d, %d, "%s", %d, "%s")',
            "",
            db_ei($this->id_list),
            db_ei($id_parent),
            db_es($body->getContent()),
            db_ei($messageDate),
            db_es($body->getContentType())
        );
        $res = db_query($sql);
        $id_message = db_insertid($res);

        // All headers of the current mail are stored in the forumml_messageheader table
        $k = 0;
        foreach ($headers as $header => $value_header) {
            $k++;
            if ($k != 1) {
                if ($header != "received") {
                    $id_header = $this->insertHeader($header);
                    if (is_array($value_header)) {
                        $value_header = implode(",", $value_header);
                    }
                    $this->insertMessageHeader($id_message, $id_header, $value_header);
                }
            }
        }

        return $id_message;
    }

    public function storeEmail(IncomingMail $incoming_mail, \ForumML_FileStorage $storage)
    {
        $message_id = $this->insertMessage($incoming_mail);
        if (! $message_id) {
            return;
        }
        $headers = $incoming_mail->getHeaders();
        if (isset($headers['date'])) {
            $date = date('Y_m_d', strtotime($headers['date']));
        } else {
            $date = date('Y_m_d');
        }
        foreach ($incoming_mail->getAttachments() as $attachment) {
            // store attachment in /var/lib/tuleap/forumml/<listname>/<Y_M_D>
            $fpath = $storage->store($attachment->getFilename(), $attachment->getContent(), $this->id_list, $date);
            $this->insertAttachment(
                $message_id,
                $attachment->getFilename(),
                $attachment->getContentType(),
                $fpath,
                $attachment->getContentID()
            );
        }
    }
}
