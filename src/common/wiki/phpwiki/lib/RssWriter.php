<?php
/*
 * Code for creating RSS 1.0.
 */

// Encoding for RSS output.
if (!defined('RSS_ENCODING')) {
    define('RSS_ENCODING', $GLOBALS['charset']);
}

/**
 * A class for writing RSS 1.0.
 *
 * @see http://purl.org/rss/1.0/spec,
 *      http://www.usemod.com/cgi-bin/mb.pl?ModWiki
 */
class RssWriter extends XmlElement
{
    public function __construct()
    {
        parent::__construct(
            'rdf:RDF',
            array('xmlns' => "http://purl.org/rss/1.0/",
            'xmlns:rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#')
        );

        $this->_modules = array(
            //Standards
        'content'    => "http://purl.org/rss/1.0/modules/content/",
        'dc'    => "http://purl.org/dc/elements/1.1/",
        'sy'    => "http://purl.org/rss/1.0/modules/syndication/",
            //Proposed
            'wiki'      => "http://purl.org/rss/1.0/modules/wiki/",
        'ag'    => "http://purl.org/rss/1.0/modules/aggregation/",
        'annotate'    => "http://purl.org/rss/1.0/modules/annotate/",
        'audio'    => "http://media.tangent.org/rss/1.0/",
        'cp'    => "http://my.theinfo.org/changed/1.0/rss/",
        'rss091'    => "http://purl.org/rss/1.0/modules/rss091/",
        'slash'    => "http://purl.org/rss/1.0/modules/slash/",
        'taxo'    => "http://purl.org/rss/1.0/modules/taxonomy/",
        'thr'    => "http://purl.org/rss/1.0/modules/threading/"
        );

        $this->_uris_seen = array();
        $this->_items = array();
    }

    public function registerModule($alias, $uri)
    {
        assert(!isset($this->_modules[$alias]));
        $this->_modules[$alias] = $uri;
    }

    // Args should include:
    //  'title', 'link', 'description'
    // and can include:
    //  'URI'
    public function channel($properties, $uri = false)
    {
        $this->_channel = $this->node('channel', $properties, $uri);
    }

    // Args should include:
    //  'title', 'link'
    // and can include:
    //  'description', 'URI'
    public function addItem($properties, $uri = false)
    {
        $this->_items[] = $this->node('item', $properties, $uri);
    }

    // Args should include:
    //  'url', 'title', 'link'
    // and can include:
    //  'URI'
    public function image($properties, $uri = false)
    {
        $this->_image = $this->node('image', $properties, $uri);
    }

    // Args should include:
    //  'title', 'description', 'name', and 'link'
    // and can include:
    //  'URI'
    public function textinput($properties, $uri = false)
    {
        $this->_textinput = $this->node('textinput', $properties, $uri);
    }

    /**
     * Finish construction of RSS.
     */
    public function finish()
    {
        if (isset($this->_finished)) {
            return;
        }

        $channel = &$this->_channel;
        $items = &$this->_items;

        $seq = new XmlElement('rdf:Seq');
        if ($items) {
            foreach ($items as $item) {
                $seq->pushContent($this->ref('rdf:li', $item));
            }
        }
        $channel->pushContent(new XmlElement('items', false, $seq));

        if (isset($this->_image)) {
            $channel->pushContent($this->ref('image', $this->_image));
            $items[] = $this->_image;
        }
        if (isset($this->_textinput)) {
            $channel->pushContent($this->ref('textinput', $this->_textinput));
            $items[] = $this->_textinput;
        }

        $this->pushContent($channel);
        if ($items) {
            $this->pushContent($items);
        }

        $this->spew();
        $this->_finished = true;
    }


    /**
     * Write output to HTTP client.
     */
    protected function spew()
    {
        header("Content-Type: application/xml; charset=" . RSS_ENCODING);
        printf("<?xml version=\"1.0\" encoding=\"%s\"?>\n", RSS_ENCODING);
        printf("<!-- generator=\"PhpWiki-%s\" -->\n", PHPWIKI_VERSION);
        $this->printXML();
    }


    /**
     * Create a new RDF <em>typedNode</em>.
     */
    protected function node($type, $properties, $uri = false)
    {
        if (! $uri) {
            $uri = $properties['link'];
        }
        $attr['rdf:about'] = $this->uniquify_uri($uri);
        return new XmlElement(
            $type,
            $attr,
            $this->elementize($properties)
        );
    }

    /**
     * Check object URI for uniqueness, create a unique URI if needed.
     */
    private function uniquify_uri($uri)
    {
        if (!$uri || isset($this->_uris_seen[$uri])) {
            $n = count($this->_uris_seen);
            $uri = $this->_channel->getAttr('rdf:about') . "#uri$n";
            assert(!isset($this->_uris_seen[$uri]));
        }
        $this->_uris_seen[$uri] = true;
        return $uri;
    }

    /**
     * Convert hash of RDF properties to <em>propertyElt</em>s.
     */
    protected function elementize($elements)
    {
        $out = array();
        foreach ($elements as $prop => $val) {
            $this->check_predicate($prop);
            $out[] = new XmlElement($prop, false, $val);
        }
        return $out;
    }

    /**
     * Check property predicates for XMLNS sanity.
     */
    private function check_predicate($name)
    {
        if (preg_match('/^([^:]+):[^:]/', $name, $m)) {
            $ns = $m[1];
            if (! $this->getAttr("xmlns:$ns")) {
                if (!isset($this->_modules[$ns])) {
                    die("$name: unknown namespace ($ns)");
                }
                $this->setAttr("xmlns:$ns", $this->_modules[$ns]);
            }
        }
    }

    /**
     * Create a <em>propertyElt</em> which references another node in the RSS.
     */
    private function ref($predicate, $reference)
    {
        $attr['rdf:resource'] = $reference->getAttr('rdf:about');
        return new XmlElement($predicate, $attr);
    }
}


// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
