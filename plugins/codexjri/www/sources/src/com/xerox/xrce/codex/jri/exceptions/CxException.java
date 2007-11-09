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

package com.xerox.xrce.codex.jri.exceptions;

/**
 * CxException is the class to manage CodeX exceptions
 * 
 */
public class CxException extends Exception {

    public CxException() {
        super();
    }

    public CxException(String message) {
        super(message);
    }

    public CxException(Throwable cause) {
        super(cause);
    }

    public CxException(String message, Throwable cause) {
        super(message, cause);
    }

}
