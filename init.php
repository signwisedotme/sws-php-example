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

session_start();

include "vendor/class.SignWise.php";
$swConf = array(
  'server' => 'https://dtm-test.signwise.me/',
  'certificate' => '../cert/cert.crt',
  'privateKey' => '../cert/private.key',
  'defaultFileProxyUrl' => 'http://www.example.com/example-fileproxy.php?',
  'defaultContainerType' => 'bdoc',
);
$sw = new SignWise($swConf);

$defaultLanguage = "et-EE";
$mobileIdCallbackServer = "http://www.example.com/";

include "functions.php";

// Collect modules
$modules = array();
if ($handle = opendir('modules')) {
  while (false !== ($entry = readdir($handle))) {
    if ($entry !== '.' && $entry !== '..' && is_dir('modules/' . $entry)) {
      $modules []= $entry;
    }
  }
  closedir($handle);
}