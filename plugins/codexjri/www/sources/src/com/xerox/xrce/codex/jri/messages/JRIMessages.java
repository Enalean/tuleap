/**
 * CodeX: Breaking Down the Barriers to Source Code Sharing
 *
 * Copyright (c) Xerox Corporation, CodeX, 2007. All Rights Reserved
 *
 * This file is licensed under the CodeX Component Software License
 *
 * @author Anne Hardyau
 * @author Marc Nazarian
 */

package com.xerox.xrce.codex.jri.messages;

import java.text.ChoiceFormat;
import java.text.MessageFormat;
import java.util.MissingResourceException;
import java.util.ResourceBundle;

/**
 * JRIMessages is the class to manage strings to be internationalized Language
 * files are in .properties file (BUNDLE_NAME_ln_cn.properties)
 */
public class JRIMessages {

    /**
     * Name of the bundle for i18n
     */
    private static final String BUNDLE_NAME = "com.xerox.xrce.codex.jri.messages.messages"; //$NON-NLS-1$

    /**
     * Resource bundle
     */
    private static final ResourceBundle RESOURCE_BUNDLE = ResourceBundle.getBundle(BUNDLE_NAME);

    /**
     * Constructor
     */
    private JRIMessages() {
    }

    /**
     * Function getString used for i18n of text
     * 
     * @param key the key used in .properties files
     * @return the localized string
     */
    public static String getString(String key) {
        try {
            return RESOURCE_BUNDLE.getString(key);
        } catch (MissingResourceException e) {
            return '!' + key + '!';
        }
    }

    /**
     * Function getString used for i18n of text with variables
     * 
     * @link http://java.sun.com/j2se/1.5.0/docs/api/java/text/MessageFormat.html
     * @param key the key used in .properties files
     * @param args any parameters that you want to pass to the string
     * @return the localized string
     */
    public static String getString(String key, Object... args) {
        try {
            return MessageFormat.format(RESOURCE_BUNDLE.getString(key), args);
        } catch (MissingResourceException e) {
            return '!' + key + '!';
        }
    }

    public static String getString(String key, ChoiceFormat format,
                                   Object... args) {
        try {
            MessageFormat message = new MessageFormat(RESOURCE_BUNDLE.getString(key));
            message.setFormatByArgumentIndex(0, format);
            return message.format(args);
        } catch (MissingResourceException e) {
            return '!' + key + '!';
        }
    }
}
