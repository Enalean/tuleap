package org.apache.solr.update.processor;
/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

import java.io.IOException;
import java.util.ArrayList;
import java.util.Collection;
import java.util.Collections;
import java.util.List;

import org.apache.lucene.index.Term;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.SolrInputField;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.StrUtils;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryResponse;
import org.apache.solr.update.AddUpdateCommand;
import org.apache.solr.update.CommitUpdateCommand;
import org.apache.solr.update.DeleteUpdateCommand;
import org.apache.solr.core.SolrResourceLoader;

public class SignatureUpdateProcessorFactory extends
    UpdateRequestProcessorFactory {

  private List<String> sigFields;
  private String signatureField;

  private Term signatureTerm;
  private boolean enabled = true;
  private String signatureClass;
  private boolean overwriteDupes;
  private SolrParams params;

  @Override
  public void init(final NamedList args) {
    if (args != null) {
      SolrParams params = SolrParams.toSolrParams(args);
      boolean enabled = params.getBool("enabled", true);
      this.enabled = enabled;

      overwriteDupes = params.getBool("overwriteDupes", true);

      signatureField = params.get("signatureField", "signatureField");

      signatureTerm = new Term(signatureField, "");

      signatureClass = params.get("signatureClass",
          "org.apache.solr.update.processor.Lookup3Signature");
      this.params = params;

      Object fields = args.get("fields");
      sigFields = fields == null ? null: StrUtils.splitSmart((String)fields, ",", true); 
      if (sigFields != null) {
        Collections.sort(sigFields);
      }
    }
  }

  public List<String> getSigFields() {
    return sigFields;
  }

  public String getSignatureField() {
    return signatureField;
  }

  public boolean isEnabled() {
    return enabled;
  }

  public String getSignatureClass() {
    return signatureClass;
  }

  public boolean getOverwriteDupes() {
    return overwriteDupes;
  }

  @Override
  public UpdateRequestProcessor getInstance(SolrQueryRequest req,
      SolrQueryResponse rsp, UpdateRequestProcessor next) {

    return new SignatureUpdateProcessor(req, rsp, this, next);

  }

  class SignatureUpdateProcessor extends UpdateRequestProcessor {
    private final SolrQueryRequest req;

    public SignatureUpdateProcessor(SolrQueryRequest req,
        SolrQueryResponse rsp, SignatureUpdateProcessorFactory factory,
        UpdateRequestProcessor next) {
      super(next);
      this.req = req;
    }

    @Override
    public void processAdd(AddUpdateCommand cmd) throws IOException {
      if (enabled) {
        SolrInputDocument doc = cmd.getSolrInputDocument();
        if (sigFields == null || sigFields.size() == 0) {
          Collection<String> docFields = doc.getFieldNames();
          sigFields = new ArrayList<String>(docFields.size());
          sigFields.addAll(docFields);
          Collections.sort(sigFields);
        }

        Signature sig = (Signature) req.getCore().getResourceLoader().newInstance(signatureClass); 
        sig.init(params);

        for (String field : sigFields) {
          SolrInputField f = doc.getField(field);
          if (f != null) {
            sig.add(field);
            Object o = f.getValue();
            if (o instanceof String) {
              sig.add((String)o);
            } else if (o instanceof Collection) {
              for (Object oo : (Collection)o) {
                if (oo instanceof String) {
                  sig.add((String)oo);
                }
              }
            }
          }
        }

        byte[] signature = sig.getSignature();
        char[] arr = new char[signature.length<<1];
        for (int i=0; i<signature.length; i++) {
          int b = signature[i];
          int idx = i<<1;
          arr[idx]= StrUtils.HEX_DIGITS[(b >> 4) & 0xf];
          arr[idx+1]= StrUtils.HEX_DIGITS[b & 0xf];
        }
        String sigString = new String(arr);
        doc.addField(signatureField, sigString);

        if (overwriteDupes) {
          cmd.updateTerm = signatureTerm.createTerm(sigString);
        }

      }

      if (next != null)
        next.processAdd(cmd);
    }

  }

  // for testing
  void setEnabled(boolean enabled) {
    this.enabled = enabled;
  }


}
