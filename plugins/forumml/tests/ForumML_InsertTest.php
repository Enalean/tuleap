<?php

require_once(dirname(__FILE__).'/../include/ForumML_mimeDecode.class.php');
require_once(dirname(__FILE__).'/../include/ForumMLInsert.class.php');
require_once(dirname(__FILE__).'/../include/ForumML_FileStorage.class.php');

Mock::generatePartial('ForumMLInsert', 'ForumMLInsertTest', array('insertMessage', 'insertAttachment'));
Mock::generate('ForumML_FileStorage');


class ForumML_InsertTest extends UnitTestCase {
	private $_fixture;

	function __construct($name="ForumML Mail DB insert tests") {
        parent::__construct($name);
        $this->_fixture     = dirname(__FILE__).'/_fixtures/samples';
	}


    function getEmailStructure($path) {
        $message                = file_get_contents($this->_fixture.'/'.$path);
        $args['include_bodies'] = TRUE;
        $args['decode_bodies']  = TRUE;
        $args['decode_headers'] = TRUE;
        $args['crlf']           = "\r\n";
        $decoder                = new ForumML_mimeDecode($message, "\r\n");
        $structure              = $decoder->decode($args);
        return $structure;
    }

    /**
     * Text only
     */
    function testInsertTextOnly() {
        $structure = $this->getEmailStructure('pure_text.mbox');

        $storage = new MockForumML_FileStorage($this);

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $txtBody='Pure text
';
        $i->expectOnce('insertMessage', array('*', $txtBody, 'text/plain; charset=ISO-8859-1; format=flowed'));
        $i->expectNever('insertAttachment');

        $i->storeEmail($structure, $storage);
    }

    /**
     * Attachment only
     */
    function testInsertAttachmentOnly() {
        $structure = $this->getEmailStructure('attachment_only.mbox');

        $storage = new MockForumML_FileStorage($this);
        $storage->setReturnValue('store', '/a/b/c');

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $txtBody='

';
        $i->expectOnce('insertMessage', array('*', $txtBody, 'text/plain; charset=ISO-8859-1; format=flowed'));
        $i->expectOnce('insertAttachment', array(2, 'lock.png', 'image/png; name="lock.png"', '/a/b/c', ''));

        $i->storeEmail($structure, $storage);
    }


    /**
     * Text + attachment
     */
    function testInsertTextWithAttachment() {
        $structure = $this->getEmailStructure('text_plus_attachment.mbox');

        $storage = new MockForumML_FileStorage($this);
        $storage->setReturnValue('store', '/a/b/c');

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $txtBody='Some text

';
        $i->expectOnce('insertMessage', array('*', $txtBody, 'text/plain; charset=ISO-8859-1; format=flowed'));
        $i->expectOnce('insertAttachment', array(2, 'lock.png', 'image/png; name="lock.png"', '/a/b/c', ''));

        $i->storeEmail($structure, $storage);
    }

    /**
     * Pure HTML sent in Text+HTML
     */
    function testInsertHTMLInTextHtmlMode() {
        $structure = $this->getEmailStructure('pure_html_text_plus_html.mbox');

        $storage = new MockForumML_FileStorage($this);
        $storage->setReturnValue('store', '/a/b/c');

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $txtBody='My *fault

*

';
        $i->expectOnce('insertMessage', array('*', $txtBody, 'text/plain; charset=ISO-8859-1; format=flowed'));
        $i->expectOnce('insertAttachment', array(2, 'message_4ACB049C.6020506.html', 'text/html; charset=ISO-8859-1', '/a/b/c', ''));

        $i->storeEmail($structure, $storage);
    }

    /**
     * Pure HTML sent in HTML Only
     */
    function testInsertHTMLInHtmlOnlyMode() {
        $structure = $this->getEmailStructure('pure_html_in_html_only.mbox');

        $storage = new MockForumML_FileStorage($this);
        $storage->setReturnValue('store', '/a/b/c');

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $i->expectOnce('insertMessage', array('*', '*', 'text/html; charset=ISO-8859-1'));
        $i->expectNever('insertAttachment');

        $i->storeEmail($structure, $storage);
    }

    /**
     * HTML with inline content in Text+HTML mode
     */
    function testInsertHtmlWithInlineContentInTextPlusHtml() {
        $structure = $this->getEmailStructure('html_with_inline_content_in_text_plus_html.mbox');

        $storage = new MockForumML_FileStorage($this);
        $storage->setReturnValue('store', '/a/b/c');

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $txtBody='My *test

*

';
        $i->expectOnce('insertMessage', array('*', $txtBody, 'text/plain; charset=ISO-8859-1; format=flowed'));
        $i->expectAt(0, 'insertAttachment', array(2, '*', 'text/html; charset=ISO-8859-1', '/a/b/c', ''));
        $i->expectAt(1, 'insertAttachment', array(2, 'lock.png', 'image/png; name="lock.png"', '/a/b/c', '<part1.02040105.07020502@codendi.org>'));
        $i->expectCallCount('insertAttachment', 2);

        $i->storeEmail($structure, $storage);
    }

    /**
     * HTML with inline content in HTML Only mode
     */
    function testInsertHtmlWithInlineContentInHtmlOnly() {
        $structure = $this->getEmailStructure('html_with_inline_content_in_html_only.mbox');

        $storage = new MockForumML_FileStorage($this);
        $storage->setReturnValue('store', '/a/b/c');

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $i->expectOnce('insertMessage', array('*', '*', 'text/html; charset=ISO-8859-1'));
        $i->expectOnce('insertAttachment', array(2, 'attachment', 'image/png', '/a/b/c', '<part1.04090204.04000103@codendi.org>'));

        $i->storeEmail($structure, $storage);
    }

    function testInsertHtmlWithInlineContentAndAttachmentInTextPlusHtml() {
        $structure = $this->getEmailStructure('html_with_inline_content_and_attch_in_text_plus_html.mbox');

        $storage = new MockForumML_FileStorage($this);
        $storage->setReturnValue('store', '/a/b/c');

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $txtBody='My *test

*

';
        $i->expectOnce('insertMessage', array('*', $txtBody, 'text/plain; charset=ISO-8859-1; format=flowed'));
        $i->expectAt(0, 'insertAttachment', array(2, '*', 'text/html; charset=ISO-8859-1', '/a/b/c', ''));
        $i->expectAt(1, 'insertAttachment', array(2, 'attachment', 'image/png', '/a/b/c', '<part1.05020200.07040300@codendi.org>'));
        $i->expectAt(2, 'insertAttachment', array(2, 'new_trk_severity_migr.png', 'image/png; name="new_trk_severity_migr.png"', '/a/b/c', ''));
        $i->expectCallCount('insertAttachment', 3);
        
        $i->storeEmail($structure, $storage);
    }

    function testInsertHtmlWithInlineContentAndAttachmentInHtmlOnly() {
        $structure = $this->getEmailStructure('html_with_inline_content_and_attch_in_html_only.mbox');

        $storage = new MockForumML_FileStorage($this);
        $storage->setReturnValue('store', '/a/b/c');

        $i = new ForumMLInsertTest($this);
        $i->setReturnValue('insertMessage', 2);

        $i->expectOnce('insertMessage', array('*', '*', 'text/html; charset=ISO-8859-1'));
        $i->expectAt(0, 'insertAttachment', array(2, 'attachment', 'image/png', '/a/b/c', '<part1.05000804.09080906@codendi.org>'));
        $i->expectAt(1, 'insertAttachment', array(2, 'new_trk_severity_migr.png', 'image/png; name="new_trk_severity_migr.png"', '/a/b/c', ''));
        $i->expectCallCount('insertAttachment', 2);

        $i->storeEmail($structure, $storage);
    } 



}

?>
