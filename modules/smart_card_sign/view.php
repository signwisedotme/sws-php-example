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
    function displaySmartCardError(err) {
      document.getElementById("smart_card_sign_result").innerHTML = err;
    }
    swPlugin.getSignCertificates(function(err, result) {
      if (err) {
        console.log('Error reading signing certificates', err);
        displaySmartCardError('Error reading signing certificates' + JSON.stringify(err));
        return;
      }
      console.log('Signing certificates', result);
      var certificates = result;
      swPlugin.getSupportedHashTypes(function(err, result) {
        if (err) {
          console.log('Error getting supported hash types', err);
          displaySmartCardError('Error getting supported hash types' + JSON.stringify(err));
          return;
        }
        var url = 'smart-card-ajax.php?module=smart_card_sign';
        var container = getSelectValue("smart_card_sign_container");
        var data = {
          certificates: certificates,
          container: container,
          supportedHashes: result
        };
        ajaxLoad(url, data, function(err, result) {
          if (err) {
            displaySmartCardError('Error preparing signature' + JSON.stringify(err));
            return;
          }
          //{"signatureId":"S0","digest":"a6e71d03c1cc523f6693b5b2daae82cdb7849021751f368a55d6771edcf94782","digestType":"SHA256"}
          swPlugin.sign(result.digest, result.digestType, function(err, result) {
            if (err) {
              console.log('Error signing', err);
              displaySmartCardError('Error signing' + JSON.stringify(err));
              return;
            }
            console.log('Signing successful', result);
            // Hash was successfully signed, now we must send the signature to server.
            var data = {
              container: container,
              signature: result
            };
            ajaxLoad(url, data, function(err, result) {
              if (err) {
                console.log('Error finalizing signature', err);
                displaySmartCardError('Error finalizing signature' + JSON.stringify(err));
                return;
              }
              displaySmartCardError("Successfully signed");
            });
          });
        });
      });
    });
  }
</script>