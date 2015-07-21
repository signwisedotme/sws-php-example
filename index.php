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

include 'init.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>PHP Full Integration Example</title>
  <link rel="stylesheet" href="style.css">
  <script src="vendor/signwise.js" type="text/javascript"></script>
  <script src="vendor/signwiseMobileId.js" type="text/javascript"></script>
  <script src="example.js"></script>
</head>
<body>

<?php

if (!empty($_GET['module']) && in_array($_GET['module'], $modules)) {
  include "modules/{$_GET['module']}/action.php";
}

// Render all modules
include "modules/index.php";

echo "<p>API Version: " . $sw->getVersion()->pkg->version . "</p>";

?>

</body>
</html>