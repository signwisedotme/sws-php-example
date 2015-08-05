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

include "init.php";

$body = json_decode(file_get_contents('php://input'), true);

if (isset($body["certificates"]) && isset($body["container"])) {
  // Prepare signature
  $options = array("supportedHashes" => $body["supportedHashes"]);
  $result = $sw->prepareSignature($body["container"], $body["container"] . ".tmp", $body["certificates"][0], $options);
  echo json_encode($result);
} elseif (isset($body["signature"]) && isset($body["container"])) {
  // Finalize signature
  $result = $sw->finalizeSignature($body["container"], $body["signature"]);
  echo json_encode($result);
} elseif (isset($body["container"])) {
  // Cancel signing
  $result = $sw->cancelSigning($body["container"]);
  if ($result === true) {
    $result = array("success" => true);
  }
  echo json_encode($result);
}