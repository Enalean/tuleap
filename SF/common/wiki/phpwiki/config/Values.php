<?php
rcs_id('$Id: Values.php 2691 2006-03-02 15:31:51Z guerin $');
/*
 Copyright 2002 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
* This is the master array that holds all of the configuration
* values.
*/
$values = array(); 

/*
This is a template for a constant or variable value.
 
$values[] = array(
    'type' => '',
    'name' => '',
    'section' => ,
    'defaultValue' => ,
    'description' => array(
        'short' => '',
        'full' => ''
    ),
    'validator' => array(
        'type' => ''
    )
);
*/

/**
* This defines the Constant that holds the name of the wiki
*/
$values[] = array(
    'type' => 'Constant',
    'name' => 'WIKI_NAME',
    'section' => 1,
    'defaultValue' => 'PhpWiki',
    'description' => array(
        'short' => 'Name of your Wiki.',
        'full' => 'This can be any string, but it should be short and informative.'
    ),
    'validator' => array(
        'type' => 'String'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'ENABLE_REVERSE_DNS',
    'section' => 1,
    'defaultValue' => true,
    'description' => array(
        'short' => 'Perform reverse DNS lookups',
        'full' => 'If set, we will perform reverse dns lookups to try to convert ' .
                  'the users IP number to a host name, even if the http server ' . 
                  'didn\'t do it for us.'
    ),
    'validator' => array(
        'type' => 'Boolean'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'ADMIN_USER',
    'section' => 1,
    'defaultValue' => "",
    'description' => array(
        'short' => 'Username of Administrator',
        'full' => 'The username of the Administrator can be just about any string.'
    ),
    'validator' => array(
        'type' => 'String'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'ADMIN_PASSWD',
    'section' => 1,
    'defaultValue' => "",
    'description' => array(
        'short' => 'Password of Administrator',
        'full' => 'The password of the Administrator, please use a secure password.'
    ),
    'validator' => array(
        'type' => 'String'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'ENCRYPTED_PASSWD',
    'section' => 1,
    'defaultValue' => true,
    'description' => array(
        'short' => 'Administrator password is encrypted.',
        'full' => 'True if the Administrator password is encrypted using the embeded tool.'
    ),
    'validator' => array(
        'type' => 'Boolean'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'ZIPDUMP_AUTH',
    'section' => 1,
    'defaultValue' => true,
    'description' => array(
        'short' => 'Require privilage to make zip dumps.',
        'full' => 'If true then only the Administrator will be allowed to make a zipped ' .
                  'archive of the Wiki.'
    ),
    'validator' => array(
        'type' => 'Boolean'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'ENABLE_RAW_HTML',
    'section' => 1,
    'defaultValue' => false,
    'description' => array(
        'short' => 'Enable the use of html in a WikiPage',
        'full' => 'If true raw html will be respected in the markup of a WikiPage. ' .
                  '*WARNING*: this is a major security hole! Do not enable on a public ' .
                  'Wiki.'
    ),
    'validator' => array(
        'type' => 'Boolean'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'STRICT_MAILABLE_PAGEDUMPS',
    'section' => 1,
    'defaultValue' => false,
    'description' => array(
        'short' => 'Page dumps are valid RFC 2822 e-mail messages',
        'full' => 'If you define this to true, (MIME-type) page-dumps (either zip ' . 
                  'dumps, or "dumps to directory" will be encoded using the ' . 
                  'quoted-printable encoding.  If you\'re actually thinking of ' . 
                  'mailing the raw page dumps, then this might be useful, since ' . 
                  '(among other things,) it ensures that all lines in the message ' . 
                  'body are under 80 characters in length. Also, setting this will ' . 
                  'cause a few additional mail headers to be generated, so that the ' . 
                  'resulting dumps are valid RFC 2822 e-mail messages. Probably, you ' . 
                  'can just leave this set to false, in which case you get raw ' . 
                  '(\'binary\' content-encoding) page dumps.'
    ),
    'validator' => array(
        'type' => 'Boolean'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'HTML_DUMP_SUFFIX',
    'section' => 1,
    'defaultValue' => '.html',
    'description' => array(
        'short' => 'Suffix for XHTML page dumps',
        'full' => 'This suffix will be appended to the name of each page for a ' .
                  'XHTML page dump and the page links will be modified accordingly.'
    ),
    'validator' => array(
        'type' => 'String'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'MAX_UPLOAD_SIZE',
    'section' => 1,
    'defaultValue' => (16 * 1024 * 1024),  // 16MB
    'description' => array(
        'short' => 'Maximum file upload size',
        'full' => 'The maximum file upload size in bytes.'
    ),
    'validator' => array(
        'type' => 'Integer'
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'MINOR_EDIT_TIMEOUT',
    'section' => 1,
    'defaultValue' => (7 * 24 * 60 * 60), // One week
    'description' => array(
        'short' => 'Length of time where \'Minor Edit\' is default',
        'full' => 'If an edit is started less than this period of time from the ' .
                  'prior edit, the \'Minor Edit\' checkbox will be set.'
    ),
    'validator' => array(
        'type' => 'Integer'
    )
);

$values[] = array(
    'type' => 'Variable',
    'name' => 'DisabledActions',
    'section' => 1,
    'defaultValue' => array(),
    'description' => array(
        'short' => 'List of actions to disable',
        'full' => 'Each action listed will be disabled.'
    ),
    'validator' => array(
        'type' => 'ArrayString',
        'list' => array(
            'browse',
            'verify',
            'diff',
            'search',
            'edit',
            'viewsource',
            'lock',
            'unlock',
            'remove',
            'upload',
            'xmlrpc',
            'zip',
            'ziphtml',
            'dumpserial',
            'dumphtml',
            'loadfile'
        )
    )
);

$values[] = array(
    'type' => 'Constant',
    'name' => 'ACCESS_LOG',
    'section' => 1,
    'defaultValue' => '',
    'description' => array(
        'short' => 'Enable and location of Wiki Access Log',
        'full' => 'If you define a location, PhpWiki will write in NCSA combined ' .
                  'format a log of all accesses.'
    ),
    'validator' => array(
        'type' => 'String'
    )
);


//$Log$
//Revision 1.4  2003/12/07 19:25:41  carstenklapp
//Code Housecleaning: fixed syntax errors. (php -l *.php)
//
//Revision 1.2  2003/01/28 18:55:25  zorloc
//I have added all of the values for Part One of our configuration values.
//
//Revision 1.1  2003/01/28 07:32:24  zorloc
//This file holds all of the config settings for the constants, variables,
//and arrays that can be customized/defined.
//
//I have done a template and one constant (WIKI_NAME).  More to follow.
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

?>