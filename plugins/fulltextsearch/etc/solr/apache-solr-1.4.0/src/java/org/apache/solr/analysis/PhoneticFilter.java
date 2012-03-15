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

package org.apache.solr.analysis;

import org.apache.commons.codec.Encoder;
import org.apache.lucene.analysis.TokenFilter;
import org.apache.lucene.analysis.TokenStream;
import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.tokenattributes.TermAttribute;
import org.apache.lucene.analysis.tokenattributes.PositionIncrementAttribute;

import java.io.IOException;

/**
 * Create tokens for phonetic matches.  See:
 * http://jakarta.apache.org/commons/codec/api-release/org/apache/commons/codec/language/package-summary.html
 *
 * @version $Id: PhoneticFilter.java 804726 2009-08-16 17:28:58Z yonik $
 */
public class PhoneticFilter extends TokenFilter 
{
  protected boolean inject = true; 
  protected Encoder encoder = null;
  protected String name = null;
  
  protected State save = null;
  private final TermAttribute termAtt;
  private final PositionIncrementAttribute posAtt;

  public PhoneticFilter(TokenStream in, Encoder encoder, String name, boolean inject) {
    super(in);
    this.encoder = encoder;
    this.name = name;
    this.inject = inject;
    this.termAtt = (TermAttribute) addAttribute(TermAttribute.class);
    this.posAtt = (PositionIncrementAttribute) addAttribute(PositionIncrementAttribute.class);    
  }

  @Override
  public boolean incrementToken() throws IOException {
    if( save != null ) {
      // clearAttributes();  // not currently necessary
      restoreState(save);
      save = null;
      return true;
    }

    if (!input.incrementToken()) return false;

    // pass through zero-length terms
    if (termAtt.termLength()==0) return true;

    String value = termAtt.term();
    String phonetic = null;
    try {
     String v = encoder.encode(value).toString();
     if (v.length() > 0 && !value.equals(v)) phonetic = v;
    } catch (Exception ignored) {} // just use the direct text

    if (phonetic == null) return true;

    if (!inject) {
      // just modify this token
      termAtt.setTermBuffer(phonetic);
      return true;
    }

    // We need to return both the original and the phonetic tokens.
    // to avoid a orig=captureState() change_to_phonetic() saved=captureState()  restoreState(orig)
    // we return the phonetic alternative first

    int origOffset = posAtt.getPositionIncrement();
    posAtt.setPositionIncrement(0);
    save = captureState();

    posAtt.setPositionIncrement(origOffset);
    termAtt.setTermBuffer(phonetic);
    return true;
  }

  @Override
  public void reset() throws IOException {
    input.reset();
    save = null;
  }
}
