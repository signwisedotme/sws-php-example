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
 * An example SignWise Services Mobile ID endpoint accessed with AJAX calls.
 * The purpose of this script is to initiate Mobile ID authentication and signing requests
 * and to check if response has been received for Mobile ID request.
 *
 * Mobile sessions get stored in text file only for sake of simplicity.
 * You should use a session database instead.
 *
 * See mobile-id-callback.php code to see how Mobile ID session statuses get stored in file.
 *
 * SignWise Services PHP SDK is a required dependency.
 */

include "init.php";

$data = json_decode(file_get_contents('php://input'));

if (isset($_GET['auth'])) {
  // Start Mobile ID authentication
  $userInfo = array("language" => $data->lang, "ssn" => $data->ssn, "msisdn" => $data->msisdn);
  $mobileSession = uniqid('', true);
  $_SESSION['mobile_id_auth_session'] = $mobileSession;
  $result = $sw->mobileAuthentication("{$mobileIdCallbackServer}mobile-id-callback.php?mobileSession=" . $mobileSession, $userInfo);
  if ($result->verificationCode) {
    echo json_encode(array('verificationCode' => $result->verificationCode));
  } else {
    echo json_encode($result);
  }
} elseif (isset($_GET['auth_result'])) {
  // Get Mobile ID authentication result
  $result = getMobileIdSessionResult('authenticate', $_SESSION['mobile_id_auth_session']);
  if ($result["status"] === "OK") {
    login($result);
  }
  echo json_encode($result);
} elseif (isset($_GET['sign'])) {
  // Start Mobile ID signing
  $userInfo = array("language" => $data->lang, "ssn" => $data->ssn, "msisdn" => $data->msisdn);
  $mobileSession = uniqid('', true);
  $_SESSION['mobile_id_sign_session'] = $mobileSession;
  $result = $sw->mobileSigning($data->container, $data->container . ".tmp", "{$mobileIdCallbackServer}mobile-id-callback.php?mobileSession=" . $mobileSession, $userInfo);
  if ($result->verificationCode) {
    echo json_encode(array('verificationCode' => $result->verificationCode));
  } else {
    echo json_encode($result);
  }
} elseif (isset($_GET['sign_result'])) {
  // Get Mobile ID signing result
  echo json_encode(getMobileIdSessionResult('sign', $_SESSION['mobile_id_sign_session']));
}

function getMobileIdSessionResult($operation, $sessionId) {
  if (($handle = fopen("out.txt", "r")) !== FALSE) {
    while (false !== ($row = fgetcsv($handle, 1000, ";"))) {
      @list($rOperation, $rSessionId, $rStatus, $rCountry, $rSsn, $rFirstName, $rLastName) = $row;
      if ($rOperation === $operation && $rSessionId === $sessionId) {
        if ($operation === "authenticate" && $rStatus === "OK") {
          $result = array(
            "status" => $rStatus,
            "country" => $rCountry,
            "identificationCode" => $rSsn,
            "firstName" => $rFirstName,
            "lastName" => $rLastName,
          );
        } else {
          $result = array(
            "status" => $rStatus,
          );
        }
        break;
      }
    }
    fclose($handle);
  }
  if (!isset($result)) {
    $result = array("status" => "PENDING");
  }
  return $result;
}