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

import java.util.HashMap;
import java.util.Map;

import junit.framework.TestCase;

import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.TokenStream;
import org.apache.solr.analysis.BaseTokenTestCase.IterTokenStream;

public class DoubleMetaphoneFilterFactoryTest extends TestCase {

  public void testDefaults() throws Exception {
    DoubleMetaphoneFilterFactory factory = new DoubleMetaphoneFilterFactory();
    factory.init(new HashMap<String, String>());
    TokenStream inputStream = new IterTokenStream("international");

    TokenStream filteredStream = factory.create(inputStream);

    assertEquals(DoubleMetaphoneFilter.class, filteredStream.getClass());

    Token token = filteredStream.next(new Token());
    assertEquals(13, token.termLength());
    assertEquals("international", new String(token.termBuffer(), 0, token
        .termLength()));

    token = filteredStream.next(new Token());
    assertEquals(4, token.termLength());
    assertEquals("ANTR", new String(token.termBuffer(), 0, token.termLength()));

    assertNull(filteredStream.next(new Token()));
  }

  public void testSettingSizeAndInject() throws Exception {
    DoubleMetaphoneFilterFactory factory = new DoubleMetaphoneFilterFactory();
    Map<String, String> parameters = new HashMap<String, String>();
    parameters.put("inject", "false");
    parameters.put("maxCodeLength", "8");
    factory.init(parameters);

    TokenStream inputStream = new IterTokenStream("international");

    TokenStream filteredStream = factory.create(inputStream);

    assertEquals(DoubleMetaphoneFilter.class, filteredStream.getClass());

    Token token = filteredStream.next(new Token());
    assertEquals(8, token.termLength());
    assertEquals("ANTRNXNL", new String(token.termBuffer(), 0, token
        .termLength()));

    assertNull(filteredStream.next(new Token()));
  }
}
