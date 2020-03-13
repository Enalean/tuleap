<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This class allows a human to read a wsdl
 */
class SOAP_WSDLRenderer
{

    /**
     * Output a html view of the given wsdl
     *
     * @param string $wsdl_uri https://example.com/plugins/statistics/soap/?wsdl
     */
    public function render($wsdl_uri)
    {
        $xml_security = new XML_Security();
        $xml_security->enableExternalLoadOfEntities();

        $proc = new XSLTProcessor();

        $xslDoc = new DOMDocument();
        $xslDoc->load(ForgeConfig::get('codendi_dir') . "/src/www/soap/wsdl-viewer.xsl");
        $proc->importStylesheet($xslDoc);

        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($this->getWSDL($wsdl_uri));
        echo $proc->transformToXML($xmlDoc);

        $xml_security->disableExternalLoadOfEntities();
    }

    private function getWSDL($wsdl_uri)
    {
        return file_get_contents($wsdl_uri, false, stream_context_create($this->getHTTPContext()));
    }

    private function getHTTPContext()
    {
        if (ForgeConfig::get('sys_use_unsecure_ssl_certificate', false)) {
            return array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                )
            );
        }
        return array();
    }
}
