<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_GetActionOnIconVisitor
*/


class Docman_View_GetActionOnIconVisitor /* implements Visitor*/
{

    public function visitFolder(&$item, $params = array())
    {
        return $params['view']->getActionOnIconForFolder($item, $params);
    }

    public function visitDocument($item, $params = array())
    {
        return 'show';
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
