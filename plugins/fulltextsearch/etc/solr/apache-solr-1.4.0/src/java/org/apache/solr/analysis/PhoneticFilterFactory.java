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

import java.lang.reflect.Method;
import java.util.HashMap;
import java.util.Map;

import org.apache.commons.codec.Encoder;
import org.apache.commons.codec.language.DoubleMetaphone;
import org.apache.commons.codec.language.Metaphone;
import org.apache.commons.codec.language.RefinedSoundex;
import org.apache.commons.codec.language.Soundex;
import org.apache.lucene.analysis.TokenStream;
import org.apache.solr.common.SolrException;

/**
 * Create tokens based on phonetic encoders
 * 
 * http://jakarta.apache.org/commons/codec/api-release/org/apache/commons/codec/language/package-summary.html
 * 
 * This takes two arguments:
 *  "encoder" required, one of "DoubleMetaphone", "Metaphone", "Soundex", "RefinedSoundex"
 * 
 * "inject" (default=true) add tokens to the stream with the offset=0
 * 
 * @version $Id: PhoneticFilterFactory.java 764276 2009-04-12 02:24:01Z yonik $
 * @see PhoneticFilter
 */
public class PhoneticFilterFactory extends BaseTokenFilterFactory 
{
  public static final String ENCODER = "encoder";
  public static final String INJECT = "inject"; // boolean
  
  private static final Map<String, Class<? extends Encoder>> registry;
  static {
    registry = new HashMap<String, Class<? extends Encoder>>();
    registry.put( "DoubleMetaphone".toUpperCase(), DoubleMetaphone.class );
    registry.put( "Metaphone".toUpperCase(),       Metaphone.class );
    registry.put( "Soundex".toUpperCase(),         Soundex.class );
    registry.put( "RefinedSoundex".toUpperCase(),  RefinedSoundex.class );
  }
  
  protected boolean inject = true;
  protected String name = null;
  protected Encoder encoder = null;

  @Override
  public void init(Map<String,String> args) {
    super.init( args );

    inject = getBoolean(INJECT, true);
    
    String name = args.get( ENCODER );
    if( name == null ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, "Missing required parameter: "+ENCODER
          +" ["+registry.keySet()+"]" );
    }
    Class<? extends Encoder> clazz = registry.get(name.toUpperCase());
    if( clazz == null ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, "Unknown encoder: "+name +" ["+registry.keySet()+"]" );
    }
    
    try {
      encoder = clazz.newInstance();
      
      // Try to set the maxCodeLength
      String v = args.get( "maxCodeLength" );
      if( v != null ) {
        Method setter = encoder.getClass().getMethod( "setMaxCodeLen", int.class );
        setter.invoke( encoder, Integer.parseInt( v ) );
      }
    } 
    catch (Exception e) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, "Error initializing: "+name + "/"+clazz, e , false);
    }
  }
  
  public PhoneticFilter create(TokenStream input) {
    return new PhoneticFilter(input,encoder,name,inject);
  }
}
