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

import org.apache.axis.AxisFault;

/**
 * CxLoginException is the class to manage CodeX exceptions at login action
 * 
 */
public class CxLoginException extends CxServerException {

    public CxLoginException(AxisFault af) {
        super(af);
    }

}
