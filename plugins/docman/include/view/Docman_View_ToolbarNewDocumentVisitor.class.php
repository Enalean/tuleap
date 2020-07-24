<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_ToolbarNewDocumentVisitor
*/


class Docman_View_ToolbarNewDocumentVisitor /* implements Visitor*/
{

    public function visitFolder(&$item, $params = [])
    {
        return $item->getId();
    }
    public function visitDocument(&$item, $params = [])
    {
        return $item->getParentId();
    }
    public function visitWiki(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitLink(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitFile(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitEmbeddedFile(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmpty(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
}
