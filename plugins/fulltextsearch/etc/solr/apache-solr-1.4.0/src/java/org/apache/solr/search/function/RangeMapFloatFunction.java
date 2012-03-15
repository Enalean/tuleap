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

package org.apache.solr.search.function;

import org.apache.lucene.index.IndexReader;
import org.apache.lucene.search.Searcher;

import java.io.IOException;
import java.util.Map;

/**
 * <code>LinearFloatFunction</code> implements a linear function over
 * another {@link org.apache.solr.search.function.ValueSource}.
 * <br>
 * Normally Used as an argument to a {@link org.apache.solr.search.function.FunctionQuery}
 *
 * @version $Id: RangeMapFloatFunction.java 816202 2009-09-17 14:08:13Z yonik $
 */
public class RangeMapFloatFunction extends ValueSource {
  protected final ValueSource source;
  protected final float min;
  protected final float max;
  protected final float target;
  protected final Float defaultVal;

  public RangeMapFloatFunction(ValueSource source, float min, float max, float target, Float def) {
    this.source = source;
    this.min = min;
    this.max = max;
    this.target = target;
    this.defaultVal = def;
  }

  public String description() {
    return "map(" + source.description() + "," + min + "," + max + "," + target + ")";
  }

  public DocValues getValues(Map context, IndexReader reader) throws IOException {
    final DocValues vals =  source.getValues(context, reader);
    return new DocValues() {
      public float floatVal(int doc) {
        float val = vals.floatVal(doc);
        return (val>=min && val<=max) ? target : (defaultVal == null ? val : defaultVal);
      }
      public int intVal(int doc) {
        return (int)floatVal(doc);
      }
      public long longVal(int doc) {
        return (long)floatVal(doc);
      }
      public double doubleVal(int doc) {
        return (double)floatVal(doc);
      }
      public String strVal(int doc) {
        return Float.toString(floatVal(doc));
      }
      public String toString(int doc) {
        return "map(" + vals.toString(doc) + ",min=" + min + ",max=" + max + ",target=" + target + ")";
      }
    };
  }

  @Override
  public void createWeight(Map context, Searcher searcher) throws IOException {
    source.createWeight(context, searcher);
  }

  public int hashCode() {
    int h = source.hashCode();
    h ^= (h << 10) | (h >>> 23);
    h += Float.floatToIntBits(min);
    h ^= (h << 14) | (h >>> 19);
    h += Float.floatToIntBits(max);
    h ^= (h << 13) | (h >>> 20);
    h += Float.floatToIntBits(target);
    if (defaultVal != null)
      h += defaultVal.hashCode();
    return h;
  }

  public boolean equals(Object o) {
    if (RangeMapFloatFunction.class != o.getClass()) return false;
    RangeMapFloatFunction other = (RangeMapFloatFunction)o;
    return  this.min == other.min
         && this.max == other.max
         && this.target == other.target
         && this.source.equals(other.source)
         && (this.defaultVal == other.defaultVal || (this.defaultVal != null && this.defaultVal.equals(other.defaultVal)));
  }
}
