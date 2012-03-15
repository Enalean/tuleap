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
 * <code>ReciprocalFloatFunction</code> implements a reciprocal function f(x) = a/(mx+b), based on
 * the float value of a field or function as exported by {@link org.apache.solr.search.function.ValueSource}.
 * <br>
 *
 * When a and b are equal, and x>=0, this function has a maximum value of 1 that drops as x increases.
 * Increasing the value of a and b together results in a movement of the entire function to a flatter part of the curve.
 * <p>These properties make this an idea function for boosting more recent documents.
 * <p>Example:<code>  recip(ms(NOW,mydatefield),3.16e-11,1,1)</code>
 * <p>A multiplier of 3.16e-11 changes the units from milliseconds to years (since there are about 3.16e10 milliseconds
 * per year).  Thus, a very recent date will yield a value close to 1/(0+1) or 1,
 * a date a year in the past will get a multiplier of about 1/(1+1) or 1/2,
 * and date two years old will yield 1/(2+1) or 1/3.
 *
 * @see FunctionQuery
 *
 * @version $Id: ReciprocalFloatFunction.java 826529 2009-10-18 21:35:35Z yonik $
 */
public class ReciprocalFloatFunction extends ValueSource {
  protected final ValueSource source;
  protected final float m;
  protected final float a;
  protected final float b;

  /**
   *  f(source) = a/(m*float(source)+b)
   */
  public ReciprocalFloatFunction(ValueSource source, float m, float a, float b) {
    this.source=source;
    this.m=m;
    this.a=a;
    this.b=b;
  }

  public DocValues getValues(Map context, IndexReader reader) throws IOException {
    final DocValues vals = source.getValues(context, reader);
    return new DocValues() {
      public float floatVal(int doc) {
        return a/(m*vals.floatVal(doc) + b);
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
        return Float.toString(a) + "/("
                + m + "*float(" + vals.toString(doc) + ')'
                + '+' + b + ')';
      }
    };
  }

  @Override
  public void createWeight(Map context, Searcher searcher) throws IOException {
    source.createWeight(context, searcher);
  }

  public String description() {
    return Float.toString(a) + "/("
           + m + "*float(" + source.description() + ")"
           + "+" + b + ')';
  }

  public int hashCode() {
    int h = Float.floatToIntBits(a) + Float.floatToIntBits(m);
    h ^= (h << 13) | (h >>> 20);
    return h + (Float.floatToIntBits(b)) + source.hashCode();
  }

  public boolean equals(Object o) {
    if (ReciprocalFloatFunction.class != o.getClass()) return false;
    ReciprocalFloatFunction other = (ReciprocalFloatFunction)o;
    return this.m == other.m
            && this.a == other.a
            && this.b == other.b
            && this.source.equals(other.source);
  }
}
