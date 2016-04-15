<?php
/**
 * Copyright (c) Enalean, 2015-2016. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Svn\ViewVCProxy;

use HTTPRequest;
use Tuleap\Svn\Repository\RepositoryManager;
use ProjectManager;
use ForgeConfig;
use CrossReferenceFactory;
use ReferenceManager;
use Codendi_HTMLPurifier;

class ViewVCProxy {

    private $repository_manager;
    private $project_manager;

    public function __construct(RepositoryManager $repository_manager, ProjectManager $project_manager) {
        $this->repository_manager = $repository_manager;
        $this->project_manager    = $project_manager;
    }

    private $office_extensions = array(
        'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
        'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
        'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
        'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
        'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
        'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template'
    );

    private function displayViewVcHeader(HTTPRequest $request) {
        $request_uri = $request->getFromServer('REQUEST_URI');

        if(strpos($request_uri, "view=patch") !== false ||
            strpos($request_uri, "view=graphimg") !== false ||
            strpos($request_uri, "annotate=") !== false ||
            strpos($request_uri, "view=redirect_path") !== false ||
            // ViewVC will redirect URLs with "&rev=" to "&revision=". This is needed by Hudson.
           strpos($request_uri, "&rev=") !== false ) {
            return false;
        }

        if(strpos($request_uri,"/?") === false &&
            strpos($request_uri,"&r1=") === false &&
            strpos($request_uri,"&r2=") === false &&
            (strpos($request_uri,"view=") === false ||
             strpos($request_uri,"view=co") !== false ) ) {
          return false;
        }

        return true;
    }

    private function buildQueryString(HTTPRequest $request) {
        $request_uri = $request->getFromServer('REQUEST_URI');

        if(strpos($request_uri,"/?") === false) {
            return $request->getFromServer('QUERY_STRING');
        } else {
            $project = $this->project_manager->getProject($request->get('group_id'));
            $repository = $this->repository_manager->getById($request->get('repo_id'), $project);
            return "roottype=svn&root=".$repository->getFullName();
        }
    }

    private function escapeStringFromServer(HTTPRequest $request, $key) {
        $string = $request->getFromServer($key);

        return escapeshellarg($string);
    }

    private function setLocaleOnFileName($path) {
        $current_locales = setlocale(LC_ALL, "0");
        // to allow $path filenames with French characters
        setlocale(LC_CTYPE, "en_US.UTF-8");

        $encoded_path = escapeshellarg($path);
        setlocale(LC_ALL, $current_locales);

        return $encoded_path;
    }

    private function setLocaleOnCommand($command) {
        ob_start();
        putenv("LC_CTYPE=en_US.UTF-8");
        passthru($command);

        return ob_get_clean();
    }

    private function getContentType($path_info, $matches) {
        //Until Apache will support the office 2007 mime types by default
        //We should keep test on extension for IE to set the right Mime Type.
        if (array_key_exists('extension', $path_info) && array_key_exists($path_info['extension'], $this->office_extensions)) {
            return $this->office_extensions[$path_info['extension']];
        }

        return $matches[1];
    }

    private function getViewVcContentType($content_type_line, $path) {
        // Set content type header from the value set by ViewVC
        // No other headers are generated by ViewVC because generate_etags
        // is set to 0 in the ViewVC config file
        $content_type_found = FALSE;
        $path_info = pathinfo($path);

        while ($content_type_line && !$content_type_found) {
            $matches = array();

            if (preg_match('/^Content-Type:(.*)$/', $content_type_line, $matches)) {
                return $this->getContentType($path_info, $matches);
            }

            $content_type_line = strtok("\n\t\r\0\x0B");
        }

        return NULL;
    }

    private function getViewVcLocationHeader($location_line) {
        // Now look for 'Location:' header line (e.g. generated by 'view=redirect_pathrev'
        // parameter, used when browsing a directory at a certain revision number)
        $location_found = FALSE;

        while ($location_line && !$location_found && strlen($location_line) > 1) {
            $matches = array();

            if (preg_match('/^Location:(.*)$/', $location_line, $matches)) {
                return $matches[1];
            }

            $location_line = strtok("\n\t\r\0\x0B");
        }

        return FALSE;
    }

    private function getPurifier() {
        return Codendi_HTMLPurifier::instance();
    }

    public function getContent(HTTPRequest $request) {
        $parse = $this->displayViewVcHeader($request);
        $headers = "";
        $body = "";
        //this is very important. default path must be /
        $path = "/";

        if ($request->getFromServer('PATH_INFO') != "") {
            $path = $request->getFromServer('PATH_INFO');

            // hack: path must always end with /
            if (strrpos($path, "/") != (strlen($path) - 1)) {
                $path .= "/";
            }
        }

        $project = $this->project_manager->getProject($request->get('group_id'));
        $repository = $this->repository_manager->getById($request->get('repo_id'), $project);

        $command = 'HTTP_COOKIE='.$this->escapeStringFromServer($request, 'HTTP_COOKIE').' '.
            'HTTP_USER_AGENT='.$this->escapeStringFromServer($request, 'HTTP_USER_AGENT').' '.
            'REMOTE_ADDR='.escapeshellarg(HTTPRequest::instance()->getIPAddress()).' '.
            'QUERY_STRING='.escapeshellarg($this->buildQueryString($request)).' '.
            'SERVER_SOFTWARE='.$this->escapeStringFromServer($request, 'SERVER_SOFTWARE').' '.
            'SCRIPT_NAME='.$this->escapeStringFromServer($request, 'SCRIPT_NAME').' '.
            'HTTP_ACCEPT_ENCODING='.$this->escapeStringFromServer($request, 'HTTP_ACCEPT_ENCODING').' '.
            'HTTP_ACCEPT_LANGUAGE='.$this->escapeStringFromServer($request, 'HTTP_ACCEPT_LANGUAGE').' '.
            'PATH_INFO='.$this->setLocaleOnFileName($path).' '.
            'PATH='.$this->escapeStringFromServer($request, 'PATH').' '.
            'HTTP_HOST='.$this->escapeStringFromServer($request, 'HTTP_HOST').' '.
            'DOCUMENT_ROOT='.$this->escapeStringFromServer($request, 'DOCUMENT_ROOT').' '.
            'CODENDI_LOCAL_INC='.$this->escapeStringFromServer($request, 'CODENDI_LOCAL_INC').' '.
            'TULEAP_REPO_NAME='.escapeshellarg($repository->getFullName()).' '.
            'TULEAP_REPO_PATH='.escapeshellarg($repository->getSystemPath()).' '.
            ForgeConfig::get('tuleap_dir').'/'.SVN_BASE_URL.'/bin/viewvc.cgi 2>&1';

        $content = $this->setLocaleOnCommand($command);

        list($headers, $body) = http_split_header_body($content);

        $content_type_line = strtok($content,"\n\t\r\0\x0B");
        $viewvc_content_type = $this->getViewVcContentType($content_type_line, $path);

        $content = substr($content, strpos($content, $content_type_line));

        $location_line = strtok($content,"\n\t\r\0\x0B");
        $viewvc_location = $this->getViewVcLocationHeader($location_line);

        if ($viewvc_location) {
            $content = substr($content, strpos($content, $location_line));
        }

        if ($parse) {
            //parse the html doc that we get from viewvc.
            //remove the http header part as well as the html header and
            //html body tags
            $cross_ref = "";
            if ($request->get('revision')) {
                $crossref_fact= new CrossReferenceFactory($repository->getName()."/".$request->get('revision'), ReferenceManager::REFERENCE_NATURE_SVNREVISION, $repository->getProject()->getID());
                $crossref_fact->fetchDatas();
                if ($crossref_fact->getNbReferences() > 0) {
                    $cross_ref .= '<h3> '.$GLOBALS['Language']->getText('cross_ref_fact_include','references').'</h3>';
                    $cross_ref .= $crossref_fact->getHTMLDisplayCrossRefs();
                }

                $revision = 'Revision '.$request->get('revision');
                $content  = str_replace("<h3>".$revision."</h3>", "<h3>".$this->getPurifier()->purify($revision) . "</h3>" . $cross_ref, $content);
            }


            $begin_body = stripos($content, "<body");
            $begin_doc  = strpos($content, ">", $begin_body) + 1;
            $length     = strpos($content, "</body>\n</html>") - $begin_doc;

            // Now insert references, and display
            return util_make_reference_links(
                substr($content, $begin_doc, $length),
                $request->get('group_id')
            );
        } else {
            echo $body;
            exit();
        }
    }
}
