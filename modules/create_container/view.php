<?php

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

?>

<h1>Create container</h1>
<div>
  <label><span>File name</span> <input name="filename"></label>
  <div id="fileInputContainer"></div>
  <label><span>Add more files</span> <button type="button" onclick="addFileInput()">+</button></label>
</div>
<div>
  <button>Create</button>
</div>

<script>
  function addFileInput() {
    var fileInputTemplate = '<?php echo generateFileSelect("Select file"); ?>';
    var div = document.createElement('div');
    div.innerHTML = fileInputTemplate;
    document.getElementById('fileInputContainer').appendChild(div.firstChild);
  }
  addFileInput();
</script>