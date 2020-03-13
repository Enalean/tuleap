<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class UserXMLExportedCollection
{

    /**
     * @var PFUser[]
     */
    private $users = array();

    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_factory;

    /**
     * @var XML_RNGValidator
     */
    private $xml_validator;

    public function __construct(XML_RNGValidator $xml_validator, XML_SimpleXMLCDATAFactory $cdata_factory)
    {
        $this->xml_validator = $xml_validator;
        $this->cdata_factory = $cdata_factory;
    }

    public function add(PFUser $user)
    {
        if (! isset($this->users[$user->getId()])) {
            $this->users[$user->getId()] = $user;
        }
    }

    /** @return string */
    public function toXML()
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <users />');

        foreach ($this->users as $user) {
            if ($user->isNone()) {
                continue;
            }

            $user_node = $xml_element->addChild('user');
            $this->cdata_factory->insert($user_node, 'id', (int) $user->getId());
            $this->cdata_factory->insert($user_node, 'username', $user->getUserName());
            $this->cdata_factory->insert($user_node, 'realname', $user->getRealName());
            $this->cdata_factory->insert($user_node, 'email', $user->getEmail());
            $this->cdata_factory->insert($user_node, 'ldapid', $user->getLdapId());
        }

        $rng_path = realpath(ForgeConfig::get('tuleap_dir') . '/src/common/xml/resources/users.rng');
        $this->xml_validator->validate($xml_element, $rng_path);

        return $this->convertToXml($xml_element);
    }

    /**
     *
     * @return String
     */
    private function convertToXml(SimpleXMLElement $xml_element)
    {
        $dom = dom_import_simplexml($xml_element)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
}
