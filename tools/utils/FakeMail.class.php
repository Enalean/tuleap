<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 * 
 */
require_once('common/mail/Mail.class.php');

/**
 * Use this class to hook email management in codendi.
 * How to use it:
 * 1. make a link on it in src/common/mail
 * 2. customize $this->_testDir to a directory the http server you run is
 *    allowed to write
 * 3. Replace in the target code 'Mail.class.php' by 'FakeMail.class.php'
 *    and 'new Mail()' by 'new FakeMail()'
 * 4. All the mail send will be stored in $this->_testDir/maillog (one after
 *    the others)
 */
class FakeMail extends Mail {

    function FakeMail() {
        parent::Mail();
        $this->_testDir = '/var/tmp/codendi_cache/mail';
    }

    function _sendmail($headers) {
        $fd = fopen($this->_testDir.'/maillog', 'a');
        if($fd) {
            $to = "To: ".$this->getTo()."\n";
            $subj = "Subject: ".$this->getEncodedSubject()."\n";
            fwrite($fd, $headers);
            fwrite($fd, $to);
            fwrite($fd, $subj);
            fwrite($fd, "\n");
            fwrite($fd, $this->getBody());
            fwrite($fd, "\n");
            fwrite($fd, "\n");
            fclose($fd);
            return true;
        }
        return false;
    }

}

?>
