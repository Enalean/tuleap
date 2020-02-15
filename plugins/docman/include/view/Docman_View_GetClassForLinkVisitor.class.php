<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_GetClassForLinkVisitor
*/


class Docman_View_GetClassForLinkVisitor /* implements Visitor*/
{

    public function visitFolder(&$item, $params = array())
    {
        return $params['view']->getClassForFolderLink();
    }

    public function visitDocument($item, $params = array())
    {
        return '';
    }
    public function visitWiki(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitLink(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitEmbeddedFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmpty(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
}
