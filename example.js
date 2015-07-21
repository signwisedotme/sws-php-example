/*
 Copyright 2015 SignWise Corporation Ltd.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */

var pluginConf = {
  lang: 'et'
};
var swPlugin = new SignWisePlugin(pluginConf);

function getSelectValue(id) {
  return document.getElementById(id).options[document.getElementById(id).selectedIndex].value;
}

function ajaxLoad(url, data, callback) {
  if (typeof callback === "undefined" && typeof data === "function") {
    callback = data;
    data = {};
  }
  var xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function ensureReadiness() {
    if (xhr.readyState < 4) {
      return;
    }
    if (xhr.status !== 200) {
      callback("xhr_failed");
      return;
    }
    if (xhr.readyState === 4) {
      try {
        var parsedResponse = JSON.parse(xhr.responseText);
        callback(false, parsedResponse);
      } catch(ex) {
        callback("invalid_json");
      }
    }
  };
  xhr.open("POST", url);
  xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
  xhr.send(JSON.stringify(data));
}