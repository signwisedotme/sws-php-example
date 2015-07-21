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

$fileStoragePath = "file-storage/";

function generateFileSelect($title, $name = "file[]", $id = false) {
  $result = "<label><span>$title</span><select name=\"{$name}\"" . ($id ? " id=\"{$id}\"" : "") . ">";
  foreach (collectFiles() as $file) {
    $file = addslashes($file);
    $result .= "<option value=\"$file\">$file</option>";
  }
  $result .= "</select></label>";
  return $result;
}

function generateSelect($items, $title, $name = "file[]") {
  if (is_string($items) && isset($_SESSION[$items])) {
    $items = $_SESSION[$items];
  }
  $result = "<label><span>$title</span><select name=\"{$name}\">";
  if (is_array($items)) {
    foreach ($items as $itemId => $itemTitle) {
      $itemTitle = addslashes($itemTitle);
      $result .= "<option value=\"$itemId\">$itemTitle</option>";
    }
  }
  $result .= "</select></label>";
  return $result;
}

// Collect files from storage
function collectFiles() {
  global $fileStoragePath;
  $result = array();
  if ($handle = opendir($fileStoragePath)) {
    while (false !== ($entry = readdir($handle))) {
      if ($entry !== '.' && $entry !== '..' && is_file($fileStoragePath . '/' . $entry)) {
        $result []= $entry;
      }
    }
    closedir($handle);
  }
  return $result;
}

function login($data) {
  if (is_object($data) && $data->certificate && $data->certificate->subject) {
    $_SESSION["user"] = (array) $data->certificate->subject;
  } elseif (is_array($data)) {
    $_SESSION["user"] = $data;
  }
}