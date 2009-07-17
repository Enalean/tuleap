<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Roberto Berto <darkelder.php.net>                           |
// +----------------------------------------------------------------------+
//
// $Id$

require_once "PEAR.php";

    /**
    * Mbox PHP class to Unix MBOX parsing and using
    * 
    * LICENSE (LGPL)
    * Copyright (C) 2002-2003 Roberto Berto
    * This library is free software; you can redistribute it and/or
    * modify it under the terms of the GNU Lesser General Public
    * License as published by the Free Software Foundation; either
    * version 2.1 of the License, or (at your option) any later version.
    *
    * This library is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    * Lesser General Public License for more details.
    *
    * You should have received a copy of the GNU Lesser General Public
    * License along with this library; if not, write to the Free Software
    * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    * Or at http://www.gnu.org/licenses/lgpl.txt
    *
    * 
    * METHODS:
    * int resource mbox->open(string file)
    *   open a mbox and return a resource id
    *
    * bool mbox->close(resource)
    *   close a mbox resource id
    *
    * int mbox->size(resource)
    *   return mbox number of messages
    *
    * string mbox->get(int resource, messageNumber)
    *   return the message number of the resource
    *
    * bool mbox->update(int resource, int messageNumber, string message)
    *   update the message offset to message (need write permission)
    *
    * bool mbox->remove(int resource, int messageNumber)
    *   remove the message messageNumber (need write permission)
    *
    * bool mbox->insert(int resource, string message[, $offset = null])
    *   add message to the end of the mbox. Offset == 0 message will
    *   be append at first message. If after == null will be the last
    *   one message. (need write permission)
    *
    * RELATED LINKS: 
    * - CPAN Perl Mail::Folder::Mbox Module
    *   Used as a start point to create this class.
    *   http://search.cpan.org/author/KJOHNSON/MailFolder-0.07/Mail/Folder/Mbox.pm
    *
    * - PHP Mime Decode PEAR Module 
    *   Use it to parse headers and body.
    *   http://pear.php.net/package-info.php?pacid=21
    *
    
    
    
    *
    * @author   Roberto Berto <darkelder@php.net>
    * @package  Mail
    * @access   public
    */
class Mail_Mbox extends PEAR
{
    /**
    * Resources data like file name, file resource, mbox number, and other 
    * cacheds things are stored here.
    *
    * Note that it isnt really a valid resource type. It is of int type.
    *
    * @var      int        
    * @access   private
    */
    var $_resources;

    /**
    * Debug mode 
    *
    * Set to true to turn on debug mode
    *
    * @var      bool
    * @access   public
    */
    var $debug = false;

    /**
      * Open a Mbox
      *
      * Open the Mbox file and return an resource identificator.
      *
      * Also, this function will process the Mbox and create a cache 
      * that tells each message start and end bytes.
      * 
      * @param  int $file   Mbox file to open
      * @return mixed       ResourceID on success else pear error class
      * @access public
      */
    function open($file)
    {
        // check if file exists else return pear error
        clearstatcache();
        if (! @stat($file)) {
            return PEAR::raiseError("Cannot open the mbox file: file doesnt exists.");
        }

        // getting next resource it to set
        $resourceId = sizeof($this->_resources) + 1;

        // setting filename to the resource id
        $this->_resources[$resourceId]["filename"] = $file;

        // opening the file
        $this->_resources[$resourceId]["fresource"] = fopen($file, "r");
        if (!is_resource($this->_resources[$resourceId]["fresource"])) {
            return PEAR::raiseError("Cannot open the mbox file: maybe without permission.");
        }

        // process the file and get the messages bytes offsets
        $this->_process($resourceId);

        return $resourceId;
    }

    /**
    * Close a Mbox
    *
    * Close the Mbox file opened by open()
    *
    * @param    int $resourceId     Mbox resouce id created by open
    * @return   mixed               true on success else pear error class
    * @access   public
    */
    function close($resourceId)
    {
        if (!is_resource($this->_resources[$resourceId]["fresource"])) {
            return PEAR::raiseError("Cannot close the mbox file because it wanst open.");
        }

        if (!fclose($this->_resources[$resourceId]["fresource"])) {
            return PEAR::raiseError("Cannot close the mbox, maybe file is being used (?)");
        }

        return true;
    }

    /**
    * Mbox Size
    * 
    * Get Mbox Number of Messages
    *
    * @param    int $resourceId     Mbox resouce id created by open
    * @return   int                 Number of messages on Mbox (starting on 1,
    *                               0 if no message exists)
    * @access   public
    */
    function size($resourceId)
    {
        if (isset($this->_resources[$resourceId]["messages"])) {
    		return sizeof($this->_resources[$resourceId]["messages"]);
        } else {
        	return 0;	
        }
    }    

    /**
    * Mbox Get 
    *
    * Get a Message from Mbox
    *
    * Note: Message number start from 0.
    *
    * @param    int $resourceId     Mbox resouce id created by open
    * @param    int $message        The number of Message
    * @return   string              Return the message else pear error class
    * @access   public
    */
    function get($resourceId, $message)
    {
        // checking if we have bytes locations for this message
        if (!is_array($this->_resources[$resourceId]["messages"][$message])) {
            return PEAR::raiseError("Message doesnt exists.");
        }

        // getting bytes locations
        $bytesStart = $this->_resources[$resourceId]["messages"][$message][0];
        $bytesEnd = $this->_resources[$resourceId]["messages"][$message][1];
	
        // a debug feature to show the bytes locations
        if ($this->debug) {
            printf("%08d=%08d<br />", $bytesStart, $bytesEnd);
        }

        // seek to start of message
        if (@fseek($this->_resources[$resourceId]["fresource"], $bytesStart) == -1) {
            return PEAR::raiseError("Cannot read message bytes");
        }

        if ($bytesEnd - $bytesStart > 0) {
            // reading and returning message (bytes to read = difference of bytes locations)
            $msg = fread($this->_resources[$resourceId]["fresource"],
                         $bytesEnd - $bytesStart) . "\n";
            return $msg;
        } else {
        	return PEAR::raiseError("Mbox file is empty");
        }
    }

    /**
    * Delete Message
    *
    * Remove a message from Mbox and save it.
    *
    * Note: messages start with 0.
    *
    * @param    int $resourceId     Mbox resouce id created by open
    * @param    int $message        The number of Message to remove
    * @return   mixed               Return true else pear error class
    * @access   public
    */
    function remove($resourceId, $message)
    {
        // checking if we have bytes locations for this message
        if (!is_array($this->_resources[$resourceId]["messages"][$message])) {
            return PEAR::raiseError("Message doesnt exists.");
        }

        // changing umask for security reasons
        $umaskOld   = umask(077);
        // creating temp file
        $ftempname  = tempnam ("/tmp", rand(0, 9));
        // returning to old umask
        umask($umaskOld);

        $ftemp      = fopen($ftempname, "w");
        if ($ftemp == false) {
            return PEAR::raiseError("Cannot create a temp file. Cannot handle this error.");            
        }

        // writting only undeleted messages 
        $messages = $this->size($resourceId);

        for ($x = 0; $x < $messages; $x++) {
            if ($x == $message) {
                continue;    
            }

            $messageThis = $this->get($resourceId, $x);
            if (is_string($messageThis)) {
                fwrite($ftemp, $messageThis, strlen($messageThis));
            }
        }

        // closing file
        $filename = $this->_resources[$resourceId]["filename"];
        $this->close($resourceId);
        fclose($ftemp);

        return $this->_move($resourceId, $ftempname, $filename);
    }

    /**
    * Update a message
    *
    * Note: Mail_Mbox auto adds \n\n at end of the message
    *
    * Note: messages start with 0.
    *
    * @param    int $resourceId     Mbox resouce id created by open
    * @param    int $message        The number of Message to updated
    * @param    string $content     The new content of the Message
    * @return   mixed               Return true else pear error class
    * @access   public
    */
    function update($resourceId, $message, $content)
    {
        // checking if we have bytes locations for this message
        if (!is_array($this->_resources[$resourceId]["messages"][$message])) {
            return PEAR::raiseError("Message doesnt exists.");
        }

        // creating temp file
        $ftempname  = tempnam ("/tmp", rand(0, 9));
        $ftemp = fopen($ftempname, "w");
        if ($ftemp == false) {
            return PEAR::raiseError("Cannot create a temp file. Cannot handle this error.");
        }

        // writting only undeleted messages
        $messages = $this->size($resourceId);

        for ($x = 0; $x < $messages; $x++) {
            if ($x == $message) {
                $messageThis = $content . "\n\n";
            } else {
                $messageThis = $this->get($resourceId, $x);
            }

            if (is_string($messageThis)) {
                fwrite($ftemp, $messageThis, strlen($messageThis));
            }
        }

        // closing file
        $filename = $this->_resources[$resourceId]["filename"];
        $this->close($resourceId);
        fclose($ftemp);

        return $this->_move($resourceId, $ftempname, $filename);
    }

    /**
    * Insert a message
    *
    * PEAR::Mail_Mbox will insert the message according its offset. 
    * 0 means before the actual message 0. 3 means before the message 3
    * (Remember: message 3 is the forth message). The default is put 
    * AFTER the last message.
    *
    * Note: PEAR::Mail_Mbox auto adds \n\n at end of the message
    *
    * @param    int $resourceId     Mbox resouce id created by open
    * @param    string $content     The content of the new Message
    * @param    int offset          Before the offset. Default: last message
    * @return   mixed               Return true else pear error class
    * @access   public
    */
    function insert($resourceId, $content, $offset = NULL)
    {
        // checking if we have bytes locations for this message
        if (!is_array($this->_resources[$resourceId])) {
            return PEAR::raiseError("ResourceId doesnt exists.");
        }

        // creating temp file
        $ftempname  = tempnam ("/tmp", rand(0, 9));
        $ftemp = fopen($ftempname, "w");
        if ($ftemp == false) {
            return PEAR::raiseError("Cannot create a temp file. Cannot handle this error.");
        }

        // writting only undeleted messages
        $messages = $this->size($resourceId);
        $content .= "\n\n";

        if ($messages == 0 && $offset !== NULL) {
            fwrite($ftemp, $content, strlen($content));
        } else {
            for ($x = 0; $x < $messages; $x++)  {
                if ($offset !== NULL && $x == $offset) {
                    fwrite($ftemp, $content, strlen($content));
                }
                $messageThis = $this->get($resourceId, $x);
    
                if (is_string($messageThis)) {
                    fwrite($ftemp, $messageThis, strlen($messageThis));
                }
            }
        }

        if ($offset === NULL) {
            fwrite($ftemp, $content, strlen($content));
        }

        // closing file
        $filename = $this->_resources[$resourceId]["filename"];
        $this->close($resourceId);
        fclose($ftemp);

        return $this->_move($resourceId, $ftempname, $filename);
    }

    /**
    * Copy a file to another
    *
    * Used internally to copy the content of the temp file to the mbox file
    *
    * @parm     int $resourceId     Resource file
    * @parm     string $ftempname   Source file - will be removed
    * @param    string $filename    Output file
    * @access   private
    */
    function _move($resourceId, $ftempname, $filename) 
    {
        // opening ftemp to read
        $ftemp = fopen($ftempname, "r");

        if ($ftemp == false) {
            return PEAR::raiseError("Cannot open temp file.");
        }

        // copy from ftemp to fp
        $fp = @fopen($filename, "w");
        if ($fp == false) {
            return PEAR::raiseError("Cannot write on mbox file.");
        }

        while (feof($ftemp) != true) {
            $strings = fread($ftemp, 4096);
            if (!fwrite($fp, $strings, strlen($strings))) {
                return PEAR::raiseError("Cannot write to file.");
            }
        }

        fclose($fp);
        fclose($ftemp);
        unlink($ftempname);

        // open another resource and substitute it to the old one
        $mid = $this->open($filename);
        $this->_resources[$resourceId] = $this->_resources[$mid];
        unset($this->_resources[$mid]);

        return true;
    }

    /**
    * Process the Mbox
    *
    * Roles:
    * - Count the messages
    * - Get start bytes and end bytes of each messages
    *
    * @param    int $resourceId     Mbox resouce id created by open
    * @access   private
    */
    function _process($resourceId)
    {
        // sanit check
        if (!is_resource($this->_resources[$resourceId]["fresource"])) {
            return PEAR::raiseError("Resource isn't valid.");
        }

        // going to start
        if (@fseek($this->_resources[$resourceId]["fresource"], 0) == -1) {
            return PEAR::raiseError("Cannot read mbox");
        }

        // starting values
        $bytes = 0;
        $lines = 0;
        if (isset($lineLast)) {        
        	unset($lineLast);
        }
        $lineThis = "";
        $bytesEnd = 0;
        
        while (feof($this->_resources[$resourceId]["fresource"]) != true) {
            // getting char by char
            $c = fgetc($this->_resources[$resourceId]["fresource"]);
            $lineThis .= $c;
            // each \n we will check things 
            if ($c === "\n") {
                // checking if start with From
                if (substr($lineThis, 0, 5) === "From ") {
                    // this line byte count is last line more 1 byte
                    $bytesStart = $bytesEnd + 1;
                    // last line byte count is this line bytes minus this line length
                    $bytesEnd = $bytes - strlen($lineThis);
                    // we will check messages after they end
                    if ($bytesStart != 1) {
                        if ($this->debug) {
                            printf("#################### from byte %08d to byte %08d ################### <br />", $bytesStart, $bytesEnd);
                        }

                        // setting new message points
                        $messagesCount = $this->size($resourceId);
                        $this->_resources[$resourceId]["messages"][$messagesCount][0] = $bytesStart;
                        $this->_resources[$resourceId]["messages"][$messagesCount][1] = $bytesEnd;
                    }
                }

                // increasing number of lines (doesn't matter)
                if ($this->debug) {
                    $lines++;
                }
 
                // last line is this line
                $lineLast = $lineThis;

                // this line is blank now
                $lineThis = "";
                if ($this->debug) {
                    printf("%08d:%08d %s<br/>", $lines, $bytes, $lineLast);
                }
            }
            $bytes++;
        }
        // last message must be made here - again same things - 

        // this line byte count is last line more 1 byte
        $bytesStart = $bytesEnd + 1;
        // last line byte count is this line bytes minus this line length
        $bytesEnd = $bytes - strlen($lineThis) - 2;
        // we will check messages after they end

        $messagesCount = $this->size($resourceId);
        $this->_resources[$resourceId]["messages"][$messagesCount][0] = $bytesStart;
        $this->_resources[$resourceId]["messages"][$messagesCount][1] = $bytesEnd;
    }    
}
  
?>
