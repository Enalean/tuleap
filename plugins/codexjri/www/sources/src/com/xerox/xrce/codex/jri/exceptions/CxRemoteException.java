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

import java.rmi.RemoteException;

/**
 * CxRemoteException is the class that manage remote exceptions returned by the
 * remote protocols. This is a class formanaging technical exceptions.
 * 
 */
public class CxRemoteException extends CxException {

    public CxRemoteException(RemoteException re) {
        this.initCause(re);
    }

}
