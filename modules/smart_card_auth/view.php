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

if (empty($_SESSION["smart_card_auth_challenge"])) {
  $challenge = '';
  $hexChars = "0123456789abcdef";
  for ($i=0; $i < 40; $i++) {
    $challenge .= $hexChars[rand(0, 15)];
  }
  $_SESSION["smart_card_auth_challenge"] = $challenge;
}

?>

<h1>Authenticate with smart card</h1>
<div>
  <label><span>Random challenge</span> <input id="smart_card_auth_challenge" disabled="disabled" value="<?php echo $_SESSION["smart_card_auth_challenge"]; ?>"></label>
  <input type="hidden" name="certificate" id="smart_card_auth_certificate">
  <input type="hidden" name="signature" id="smart_card_auth_signature">
</div>
<div>
  <button type="button" onclick="smartCardAuth()">Authenticate</button>
  <p id="smart_card_auth_result"></p>
</div>

<p id="messages"></p>
<script>
function smartCardAuth() {
  console.log(this);
  function displaySmartCardError(err) {
    document.getElementById("smart_card_auth_result").innerHTML = err;
  }
  swPlugin.getAuthCertificates(function(err, result) {
    if (err) {
      console.log('Error reading authentication certificates', err);
      displaySmartCardError('Error reading authentication certificates' + JSON.stringify(err));
      return;
    }
    console.log('Authentication certificates', result);
    document.getElementById("smart_card_auth_certificate").value = result;
    var challenge = document.getElementById("smart_card_auth_challenge").value;
    swPlugin.authenticate(challenge, function(err, result) {
      if (err) {
        console.log('Error authenticating', err);
        displaySmartCardError('Error authenticating' + JSON.stringify(err));
        return;
      }
      console.log('Authentication successful', result);
      // Challenge was successfully signed, now we must verify the signature server-side.
      document.getElementById("smart_card_auth_signature").value = result;
      document.getElementById("form_smart_card_auth").submit();
    });
  });
}
</script>