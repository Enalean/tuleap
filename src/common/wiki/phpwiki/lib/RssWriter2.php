<?php rcs_id('$Id: RssWriter2.php,v 1.2 2005/07/24 09:52:59 rurban Exp $');
/*
 * Code for creating RSS 2.0
 * Author: Reini Urban for PhpWiki
 */

// Encoding for RSS output.
include_once("lib/RssWriter.php");

/**
 * A class for writing RSS 2.0 with xml-rpc notifier
 *
 * @see http://blogs.law.harvard.edu/tech/rss,
 *      http://www.usemod.com/cgi-bin/mb.pl?ModWiki
 * no namespace! 
 * http://sourceforge.net/mailarchive/forum.php?thread_id=4872845&forum_id=37467
 */
class RssWriter2 extends RssWriter
{
    function RssWriter2 () {
        $this->XmlElement('rss',
                          array('version' => "2.0"));

        // not used. no namespaces should be used.
	$this->_modules = array(
            //Standards
	    'content'	=> "http://purl.org/rss/1.0/modules/content/",
	    'dc'	=> "http://purl.org/dc/elements/1.1/",
	    'sy'	=> "http://purl.org/rss/1.0/modules/syndication/",
            //Proposed
            'wiki'      => "http://purl.org/rss/1.0/modules/wiki/",
	    'ag'	=> "http://purl.org/rss/1.0/modules/aggregation/",
	    'annotate'	=> "http://purl.org/rss/1.0/modules/annotate/",
	    'audio'	=> "http://media.tangent.org/rss/1.0/",
	    'cp'	=> "http://my.theinfo.org/changed/1.0/rss/",
	    'rss091'	=> "http://purl.org/rss/1.0/modules/rss091/",
	    'slash'	=> "http://purl.org/rss/1.0/modules/slash/",
	    'taxo'	=> "http://purl.org/rss/1.0/modules/taxonomy/",
	    'thr'	=> "http://purl.org/rss/1.0/modules/threading/"
	    );
	$this->_uris_seen = array();
        $this->_items = array();
    }

    // Required args: (applying defaults)
    //  'domain', 'port', 'path', 'registerProcedure', 'protocol'
    // Optional args:
    //  none
    function cloud($properties) {
        // xml-rpc or soap or http-post
        if (!isset($properties['protocol'])) $properties['protocol'] = 'xml-rpc'; 
        if (!isset($properties['registerProcedure'])) 
            $properties['registerProcedure'] = 'rssPleaseNotify';
        if (!isset($properties['path'])) $properties['path'] = DATA_PATH.'/RPC2.php';
        if (!isset($properties['port'])) 
            $properties['port'] = !SERVER_PORT 
                ? '80' 
                : (SERVER_PROTOCOL == 'https' ? '443' : '80');
        if (!isset($properties['domain'])) $properties['domain'] = SERVER_NAME;
        $this->_cloud = $this->__node('cloud', $properties);
    }

    /**
     * Write output to HTTP client.
     */
    function __spew() {
        header("Content-Type: application/rss+xml; charset=" . RSS_ENCODING);
        printf("<?xml version=\"1.0\" encoding=\"%s\"?>\n", RSS_ENCODING);
        printf("<!-- generator=\"PhpWiki-%s\" -->\n", PHPWIKI_VERSION);
        //RSS2 really is 0.92
        //<!DOCTYPE rss SYSTEM "http://my.netscape.com/publish/formats/rss-0.92.dtd">
        echo "<!DOCTYPE rss [<!ENTITY % HTMLlat1 PUBLIC \"-//W3C//ENTITIES Latin 1 for XHTML//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml-lat1.ent\">\n";
        echo "              %HTMLlat1;]>\n";
        $this->printXML();
    }

};

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>