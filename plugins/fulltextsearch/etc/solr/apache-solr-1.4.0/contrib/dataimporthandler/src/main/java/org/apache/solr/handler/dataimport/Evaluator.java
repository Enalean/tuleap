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
package org.apache.solr.handler.dataimport;

/**
 * <p>
 * Pluggable functions for resolving variables
 * </p>
 * <p>
 * Implementations of this abstract class must provide a public no-arg constructor.
 * </p>
 * <p>
 * Refer to <a
 * href="http://wiki.apache.org/solr/DataImportHandler">http://wiki.apache.org/solr/DataImportHandler</a>
 * for more details.
 * </p>
 * <b>This API is experimental and may change in the future.</b>
 *
 * @version $Id: Evaluator.java 745734 2009-02-19 05:28:48Z shalin $
 * @since solr 1.3
 */
public abstract class Evaluator {

  /**
   * Return a String after processing an expression and a VariableResolver
   *
   * @see org.apache.solr.handler.dataimport.VariableResolver
   * @param expression string to be evaluated
   * @param context instance
   * @return the value of the given expression evaluated using the resolver
   */
  public abstract String evaluate(String expression, Context context);
}
