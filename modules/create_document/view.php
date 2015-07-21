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
<h1>Create document</h1>
<div>
  <label><span>Name</span> <input name="name"></label>
  <label><span>Output path</span> <input name="output_path" value="document.pdf"></label>
  <?php echo generateSelect('templates', 'Template', 'template'); ?>
  <label><span>Fields</span> <textarea rows="3" name="fields">[{"placeholder":"[name]", "value": "John Smith"}, {"placeholder":"[location]", "value": "Tallinn"}, {"placeholder": "[date]", "value": "31.12.2015"}]</textarea>
  </label>
</div>
<div>
  <button>Create document</button>
</div>
