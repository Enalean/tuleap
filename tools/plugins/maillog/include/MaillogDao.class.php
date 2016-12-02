<?php
/*
 * Copyright © STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once('common/dao/include/DataAccessObject.class.php');

define('PLUGIN_MAILLOG_MESSAGE_ID', '1');
define('PLUGIN_MAILLOG_DATE', '2');
define('PLUGIN_MAILLOG_FROM', '3');
define('PLUGIN_MAILLOG_SUBJECT', '4');
define('PLUGIN_MAILLOG_TO', '5');
define('PLUGIN_MAILLOG_CC', '6');
define('PLUGIN_MAILLOG_BCC', '7');

class MaillogDao extends DataAccessObject {
    function getAllMessages() {
        $qry = 'SELECT SQL_CALC_FOUND_ROWS * FROM plugin_maillog_message ORDER BY id_message DESC';
        return $this->retrieve($qry);
    }

    public function searchMessageById($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM plugin_maillog_message WHERE id_message = $id";

        return $this->retrieve($sql);
    }

    function getNbMessages() {
        $dar = $this->retrieve('SELECT FOUND_ROWS() as nb');
        if(!$this->da->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['nb'];
        } else {
            return false;
        }
    }

    function getAllHeaders($message_id) {
        $qry = 'SELECT h.name, mh.value'.
            ' FROM plugin_maillog_messageheader mh'.
            '  JOIN  plugin_maillog_header h USING (id_header)'.
            ' WHERE mh.id_message='.db_ei($message_id).
            ' ORDER BY h.name';
        return $this->retrieve($qry);
    }

    function insertMessageHeader($id_message, $id_header, $value) {
    	$qry = sprintf('INSERT INTO plugin_maillog_messageheader'.
    					' (id_message, id_header, value)'.
    					' VALUES (%d,%d,"%s")',
    					$id_message, $id_header, db_escape_string($value));
    	$this->update($qry);
    }

    function insertBody($mail) {
        if (is_a($mail, 'Codendi_Mail')) {
            $body      = $mail->getBodyText();
            $html_body = $mail->getBodyHTML();
        } else {
            $body      = $mail->getBody();
            $html_body = '';
        }

        $qry = sprintf('INSERT INTO plugin_maillog_message'.
                       ' (body, html_body)'.
                       ' VALUES ("%s", "%s")',
                       db_escape_string($body),
                       db_escape_string($html_body));
        $this->update($qry);
        if(!$this->da->isError()) {
            $dar = $this->retrieve('SELECT LAST_INSERT_ID() AS id');
            if($row = $dar->getRow()) {
                return $row['id'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function insertMail($mail) {
        $id_message = $this->insertBody($mail);
        if($id_message !== false) {
            $this->insertMessageHeader($id_message, PLUGIN_MAILLOG_FROM, $mail->getFrom());
            $this->insertMessageHeader($id_message, PLUGIN_MAILLOG_SUBJECT, $mail->getSubject());
            $this->insertMessageHeader($id_message, PLUGIN_MAILLOG_DATE, date('r'));
            $this->insertMessageHeader($id_message, PLUGIN_MAILLOG_TO, $mail->getTo());
            $this->insertMessageHeader($id_message, PLUGIN_MAILLOG_CC, $mail->getCc());
            $this->insertMessageHeader($id_message, PLUGIN_MAILLOG_BCC, $mail->getBcc());
        }
    }

    function deleteAllMessages() {
        $qry = 'TRUNCATE TABLE plugin_maillog_messageheader';
        $this->update($qry);
        $qry = 'TRUNCATE TABLE plugin_maillog_message';
        $this->update($qry);
    }

}

?>
