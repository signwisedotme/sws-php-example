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

// Display modules
foreach ($modules as $module) {
  echo '<form method="post" action="?module=' . $module . '" class="card" enctype="multipart/form-data" id="form_' . $module .'">';
  include "modules/{$module}/view.php";
  if (isset($actionSuccess) && $_GET['module'] === $module) {
    echo $actionSuccess ? "<div class=\"result-success\">Action was successful.</div>" : "<div class=\"result-fail\">Action failed.</div>";
  }
  echo '</form>';
}