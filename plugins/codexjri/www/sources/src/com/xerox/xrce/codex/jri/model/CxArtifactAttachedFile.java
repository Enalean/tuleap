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

package com.xerox.xrce.codex.jri.model;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.rmi.RemoteException;

import javax.activation.MimetypesFileTypeMap;

import org.apache.axis.AxisFault;
import org.apache.axis.encoding.Base64;

import com.xerox.xrce.codex.jri.exceptions.CxException;
import com.xerox.xrce.codex.jri.exceptions.CxRemoteException;
import com.xerox.xrce.codex.jri.exceptions.CxServerException;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactFile;

/**
 * CxArtifactAttachedFile is the class for files attached to an artifact.
 * 
 */
public class CxArtifactAttachedFile extends CxFromServer {

    /**
     * The artifact this attached belongs to
     */
    private CxArtifact artifact;

    /**
     * The ID of this attached file
     */
    private int attachedFileID;

    /**
     * The ID of the artifact this attached file belongs tos
     */
    private int artifactID;

    /**
     * The name of this file
     */
    private String fileName;

    /**
     * The path of this file
     */
    private String filePath;

    /**
     * The description of this file
     */
    private String description;

    /**
     * The size of this file (in bytes)
     */
    private int size;

    /**
     * The mime-type of this attached file
     */
    private String mimeType;

    /**
     * The date this file has been added to the artifact (unix time stamp)
     */
    private int addDate;

    /**
     * The name of the CodeX user who add this attached file
     */
    private String submittedBy;

    /**
     * The content of this file, encoded in Base64
     */
    private String base64BinaryData = null;

    /**
     * Constructor from an ArtifactFile Object
     * 
     * @param server the server this attached file belongs to
     * @param artifactFile the ArtifactFile Object
     */
    public CxArtifactAttachedFile(CxServer server, ArtifactFile artifactFile) {
        super(server);
        this.attachedFileID = artifactFile.getId();
        this.artifactID = artifactFile.getArtifact_id();
        this.fileName = artifactFile.getFilename();
        this.filePath = null;
        this.description = artifactFile.getDescription();
        this.size = artifactFile.getFilesize();
        this.mimeType = artifactFile.getFiletype();
        this.addDate = artifactFile.getAdddate();
        this.submittedBy = artifactFile.getSubmitted_by();
    }

    /**
     * Constructor from datas
     * 
     * @param server the server this attached file belongs to
     * @param file the file to add
     * @param description the desciprtion of the file to add
     * @param artifactID the Id of the artifact to add the file to
     */
    public CxArtifactAttachedFile(CxServer server, File file,
            String description, int artifactID) {
        super(server);
        this.artifactID = artifactID;
        this.fileName = file.getName();
        this.filePath = file.getPath();
        this.description = description;
        this.size = 0;
        this.mimeType = "";
        this.addDate = 0;
        this.submittedBy = "";
    }

    /**
     * Returns the ID of this attached file
     * 
     * @return the ID of this attached file
     */
    public int getID() {
        return this.attachedFileID;
    }

    /**
     * Returns the ID of the artifact this attached file belongs to
     * 
     * @return the ID of the artifact this attached file belongs to
     */
    public int getArtifactID() {
        return this.artifactID;
    }

    /**
     * Returns the name of this attached file
     * 
     * @return the name of this attached file
     */
    public String getName() {
        return this.fileName;
    }

    /**
     * Returns the path of this attached file
     * 
     * @return the path of this attached file
     */
    public String getPath() {
        return this.filePath;
    }

    /**
     * Returns the description of this attached file
     * 
     * @return the description of this attached file
     */
    public String getDescription() {
        return this.description;
    }

    /**
     * Returns the size of this attached file (in bytes)
     * 
     * @return the size of this attached file (in bytes)
     */
    public int getSize() {
        return this.size;
    }

    /**
     * Returns the mime-type of this attached file. If the mime-type is not set,
     * we try to guess it
     * 
     * @return the mime-type of this attached file
     */
    public String getMimeType() {
        if (this.mimeType == null || ("").equals(this.mimeType)) {
            return getGuessedMimeType();
        } else {
            return this.mimeType;
        }
    }

    /**
     * Returns the date where this attached file has been added
     * 
     * @return the date where this attached file has been added
     */
    public int getAddDate() {
        return this.addDate;
    }

    /**
     * Return the name of the CodeX user that added this attached file
     * 
     * @return the name of the CodeX user that added this attached file
     */
    public String getSubmittedBy() {
        return this.submittedBy;
    }

    /**
     * Try to guess the mime-type of this attached file.
     * 
     * @return the mime-type of this attached file.
     */
    private String getGuessedMimeType() {
        return (new MimetypesFileTypeMap().getContentType(this.fileName));
    }

    /**
     * Upload and returns the content of the file encoded in Base64
     * 
     * @return the content of the file encoded in Base64
     * @throws FileNotFoundException
     * @throws IOException
     */
    public String uploadBase64BinaryData() throws FileNotFoundException,
                                          IOException {
        // setting the binary data
        File file = new File(this.filePath);
        FileInputStream fileInputStream = null;
        int length = (int) file.length();
        byte[] bytes = new byte[length];
        try {
            fileInputStream = new FileInputStream(file);
            fileInputStream.read(bytes, 0, length);
        } catch (FileNotFoundException e) {
            throw e;
        } catch (IOException ioe) {
            throw ioe;
        }
        return Base64.encode(bytes);
    }

    /**
     * Returns the content of the file encoded in Base64
     * 
     * @param refresh true if you want to enforce to refresh the data from the
     *        CodeX server (some changes can have be done by other users), false
     *        otherwise
     * @return the content of the file encoded in Base64
     * @throws CxException
     */
    public String getBase64BinaryData(boolean refresh) throws CxException {
        if (this.base64BinaryData == null || refresh) {
            try {
                // Get the content of the attached file with a SOAP call
                ArtifactFile artFile = null;
                artFile = server.getBinding().getArtifactAttachedFile(
                    server.getSession().getSession_hash(),
                    this.getArtifact().getTracker().getGroup().getId(),
                    this.getArtifact().getTracker().getGroupArtifactID(),
                    this.getArtifact().getId(), this.getID());

                this.base64BinaryData = Base64.encode(artFile.getBin_data());
            } catch (AxisFault axisFault) {
                throw new CxServerException(axisFault);
            } catch (RemoteException re) {
                throw new CxRemoteException(re);
            }
        }
        return this.base64BinaryData;
    }

    /**
     * The artifact this attached file belongs to
     * 
     * @return the artifact this attached file belongs to
     */
    public CxArtifact getArtifact() {
        return artifact;
    }

    /**
     * Set the artifact this attached file belongs to
     * 
     * @param artifact the artifact this attached file belongs to
     */
    public void setArtifact(CxArtifact artifact) {
        this.artifact = artifact;
    }

}
