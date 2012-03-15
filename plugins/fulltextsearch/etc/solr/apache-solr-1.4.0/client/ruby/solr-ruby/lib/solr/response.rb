# The ASF licenses this file to You under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

module Solr; module Response; end; end
require 'solr/response/base'
require 'solr/response/xml'
require 'solr/response/ruby'
require 'solr/response/ping'
require 'solr/response/add_document'
require 'solr/response/modify_document'
require 'solr/response/standard'
require 'solr/response/spellcheck'
require 'solr/response/dismax'
require 'solr/response/commit'
require 'solr/response/delete'
require 'solr/response/index_info'
require 'solr/response/optimize'
require 'solr/response/select'