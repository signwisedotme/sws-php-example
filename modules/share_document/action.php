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

if (!empty($_POST["expires"])) {
  $recipients = json_decode($_POST["recipients"], true);
  for ($i = 0; $i < count($recipients); $i++) {
    if (!isset($recipients[$i]["tmpPath"])) {
      $recipients[$i]["tmpPath"] = $_POST["container"] . ".tmp";
    }
    if (!isset($recipients[$i]["language"])) {
      $recipients[$i]["language"] = $defaultLanguage;
    }
  }
  $result = $sw->createDocumentShare($_POST["document"], $_POST["expires"], $recipients, $_SESSION["documents"][$_POST["document"]]);
  if (is_object($result) && isset($result->id)) {
    if (!isset($_SESSION['document_shares'])) {
      $_SESSION['document_shares'] = array();
    }
    $_SESSION['document_shares'][$result->id] = $result->name;
    $actionSuccess = true;
  } else {
    $actionSuccess = false;
    print_r($result);
  }

}