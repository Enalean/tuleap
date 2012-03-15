package org.apache.solr.core;
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

import org.apache.lucene.queryParser.ParseException;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.search.FunctionQParser;
import org.apache.solr.search.ValueSourceParser;
import org.apache.solr.search.function.DocValues;
import org.apache.solr.search.function.SimpleFloatFunction;
import org.apache.solr.search.function.ValueSource;


/**
 * Mock ValueSource parser that doesn't do much of anything
 *
 **/
public class DummyValueSourceParser extends ValueSourceParser {
  private NamedList args;

  public void init(NamedList args) {
    this.args = args;
  }

  public ValueSource parse(FunctionQParser fp) throws ParseException {
    ValueSource source = fp.parseValueSource();
    ValueSource result = new SimpleFloatFunction(source) {
      protected String name() {
        return "foo";
      }

      protected float func(int doc, DocValues vals) {
        float result = 0;
        return result;
      }
    };
    return result;
  }


}
