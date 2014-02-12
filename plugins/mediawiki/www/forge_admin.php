<?php

require_once 'pre.php';

require_once dirname(__FILE__) .'/../include/MediawikiAdminController.class.php';

$service = $request->getProject()->getService('plugin_mediawiki');

$controller = new MediawikiAdminController();
$vWhiteList = new Valid_WhiteList('action', array('save', 'index'));
$vWhiteList->required();

$action = $request->getValidated('action', $vWhiteList, 'index');
$controller->$action($service, $request);