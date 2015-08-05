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

?>

<h1>Sign with smart card</h1>
<div>
  <?php echo generateFileSelect('Container', 'container', 'smart_card_sign_container'); ?>
</div>
<div>
  <button type="button" onclick="smartCardSign()">Sign</button>
  <p id="smart_card_sign_result"></p>
</div>

<p id="messages"></p>
<script>
  function smartCardSign() {
    console.log(this);
    function displaySmartCardResult(str) {
      document.getElementById("smart_card_sign_result").innerHTML = str;
    }
    swPlugin.getSignCertificates(function(err, result) {
      if (err) {
        console.log('Error reading signing certificates', err);
        displaySmartCardResult('Error reading signing certificates' + JSON.stringify(err));
        return;
      }
      console.log('Signing certificates', result);
      var certificates = result;
      swPlugin.getSupportedHashTypes(function(err, result) {
        if (err) {
          console.log('Error getting supported hash types', err);
          displaySmartCardResult('Error getting supported hash types' + JSON.stringify(err));
          return;
        }
        var url = 'smart-card-ajax.php?module=smart_card_sign';
        var container = getSelectValue("smart_card_sign_container");
        var data = {
          certificates: certificates,
          container: container,
          supportedHashes: result
        };
        ajaxLoad(url, data, function(err, result) { // Prepare signing in server
          if (err) {
            displaySmartCardResult('Error preparing signature' + JSON.stringify(err));
            return;
          }
          swPlugin.sign(result.digest, result.digestType, function(err, result) {
            if (err) {
              console.log('Error signing', err);
              ajaxLoad(url, {container: container}, function(err, result) { // Cancel signing in server
                if (err) {
                  console.log('Error cancelling signature', err);
                  displaySmartCardResult('Error cancelling signature' + JSON.stringify(err));
                  return;
                }
                displaySmartCardResult(result.success ? "Cancelled" : "Cancelling failed: " + JSON.stringify(result));
              });
              displaySmartCardResult('Error signing' + JSON.stringify(err));
              return;
            }
            console.log('Signing successful', result);
            // Hash was successfully signed, now we must send the signature to server.
            var data = {
              container: container,
              signature: result
            };
            ajaxLoad(url, data, function(err, result) { // Finalize signing in server
              if (err) {
                console.log('Error finalizing signature', err);
                displaySmartCardResult('Error finalizing signature' + JSON.stringify(err));
                return;
              }
              displaySmartCardResult(result.container ? "Successfully signed" : "Signing failed: " + JSON.stringify(result));
            });
          });
        });
      });
    });
  }
</script>