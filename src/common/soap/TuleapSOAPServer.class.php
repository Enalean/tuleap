<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

class TuleapSOAPServer extends SoapServer
{

    public function __construct($wsdl, ?array $options = null)
    {
        if (ForgeConfig::get('sys_use_unsecure_ssl_certificate') === true) {
            $wsdl = $this->fetchUnsecureWsdl($wsdl);
        }

        if ($options === null) {
            $options = array();
        }
        $options['soap_version'] = SOAP_1_2;
        $xml_security = new XML_Security();
        $xml_security->enableExternalLoadOfEntities();
        parent::__construct($wsdl, $options);
        $xml_security->disableExternalLoadOfEntities();
    }

    /**
     * Do not let PHP fetch WSDL
     *
     * PHP is marvelous, stream context given to Soap server is not taken into account (at least for SSL).
     * So we need to fetch it by hand before creating SoapServer object. Yay PHP!
     *
     * @param $wsdl_url
     * @return string
     * @throws SoapFault
     */
    private function fetchUnsecureWsdl($wsdl_url)
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        $wsdl_content = file_get_contents($wsdl_url, false, $context);
        if ($wsdl_content == "") {
            throw new SoapFault(255, "Unable to fetch WSDL");
        }
        $wsdl_file_path = ForgeConfig::get('codendi_cache_dir') . '/wsdl-' . md5($wsdl_url);
        file_put_contents($wsdl_file_path, $wsdl_content);
        return $wsdl_file_path;
    }
}
