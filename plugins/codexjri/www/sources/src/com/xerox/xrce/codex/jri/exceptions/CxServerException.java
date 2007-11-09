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
import org.w3c.dom.DOMException;
import org.w3c.dom.Element;

/**
 * CxServerException is the class that manage exceptions returned by the CodeX
 * server. Exceptions from the CodeX server are returned as SOAPFault and
 * converted in {@link AxisFault}
 * 
 */
public class CxServerException extends CxException {

    /**
     * Constructor for CxServerException. Construct a CxServerException from an
     * {@link AxisFault}
     * 
     * @param af the AxisFault
     */
    public CxServerException(AxisFault af) {
        this.initCause(af);
    }

    /**
     * Returns the code of the Exception. SOAPFault have a code attribute
     * 
     * @return the code of this exception
     */
    public String getCode() {
        return ((AxisFault) this.getCause()).getFaultCode().getLocalPart();
    }

    /**
     * Returns the actor of the exception. The actor is the action or function
     * that threw the exception
     * 
     * @return the actor of this exception
     */
    public String getActor() {
        return ((AxisFault) this.getCause()).getFaultActor();
    }

    /**
     * Returns the description of the exception
     * 
     * @return the description of this exception
     */
    public String getDescription() {
        return ((AxisFault) this.getCause()).getFaultString();
    }

    public String getDetails() {
        String details = "";
        Element[] elements = ((AxisFault) this.getCause()).getFaultDetails();
        for (Element element : elements) {
            try {
                details += element.getNodeValue();
            } catch (DOMException de) {
                // do nothing
            }
        }
        return details; // TODO review : getFaultDetails return an XML tree (see
        // http://ws.apache.org/axis/java/apiDocs/org/apache/axis/AxisFault.html
        // for details)
    }

    @Override
    public String getMessage() {
        return this.getDescription();
    }

}
