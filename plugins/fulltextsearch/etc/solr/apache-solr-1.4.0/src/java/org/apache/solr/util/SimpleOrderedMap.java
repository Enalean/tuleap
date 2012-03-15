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
package org.apache.solr.util;

import java.util.List;
import java.util.Map;

/**
 * This class is scheduled for deletion.  Please update your code to the moved package.
 *
 * @deprecated Use org.apache.solr.common.util.SimpleOrderedMap<T>.
 */
@Deprecated
public class SimpleOrderedMap<T> extends org.apache.solr.common.util.SimpleOrderedMap<T> {

  public SimpleOrderedMap() {
    super();
  }

  @Deprecated
  public SimpleOrderedMap(List nameValuePairs) {
    super(nameValuePairs);
  }

  public SimpleOrderedMap(Map.Entry<String, T> [] nameValuePairs) {
    super(nameValuePairs);
  }
}
