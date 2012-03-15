package org.apache.solr.analysis;

/**
 * Copyright 2004 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
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
import java.util.HashMap;
import java.util.Map;

public class LengthFilterTest extends BaseTokenTestCase {

  public void test() throws IOException {
    LengthFilterFactory factory = new LengthFilterFactory();
    Map<String, String> args = new HashMap<String, String>();
    args.put(LengthFilterFactory.MIN_KEY, String.valueOf(4));
    args.put(LengthFilterFactory.MAX_KEY, String.valueOf(10));
    factory.init(args);
    String[] test = {"foo", "foobar", "super-duper-trooper"};
    String gold = "foobar";
    String out = tsToString(factory.create(new IterTokenStream(test)));
    assertEquals(gold.toString(), out);
  }
}