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

/**
 * Example to handle callbacks with Mobile ID signing and authentication result from SignWise Services servers.
 * Mobile sessions get stored in text file only for sake of simplicity.
 * You should use a session database instead.
 */

$body = json_decode(file_get_contents("php://input"));
if (is_object($body)) {
  if ($body->event === "mobile-sign") {
    $status = $body->error ? $body->error->statusCode : "OK";
    file_put_contents("out.txt", "sign;{$_GET["mobileSession"]};{$status}\n", FILE_APPEND);
  } elseif ($body->event === "mobile-login") {
    $status = $body->error ? $body->error->code : "OK";
    if (!empty($body->success)) {
      $additionalData = ";" . $body->country . ";" . $body->personalCode . ";" . $body->firstName . ";" . $body->lastName;
    } else {
      $additionalData = "";
    }
    file_put_contents("out.txt", "authenticate;{$_GET["mobileSession"]};{$status}{$additionalData}\n", FILE_APPEND);
  }
}
