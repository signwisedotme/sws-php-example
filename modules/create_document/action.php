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

if (!empty($_POST['name'])) {
  $fields = json_decode($_POST["fields"], true);
  $result = $sw->createDocument($_POST["template"], $_POST["output_path"], $fields, $_POST["name"]);
  if (is_object($result) && $result->id) {
    $actionSuccess = true;
    if (!isset($_SESSION['documents'])) {
      $_SESSION['documents'] = array();
    }
    $_SESSION['documents'][$result->id] = $result->name;
  } else {
    $actionSuccess = false;
    print_r($result);
  }
}