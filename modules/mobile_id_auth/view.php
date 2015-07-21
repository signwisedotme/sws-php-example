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
<h1>Authenticate with Mobile ID</h1>
<div>
  <label><span>MSISDN</span> <input name="msisdn" id="mobile_id_auth_msisdn" value="37200007"></label>
  <label><span>SSN</span> <input name="ssn" id="mobile_id_auth_ssn" value="14212128025"></label>
  <p id="mobile_id_auth_status"></p>
  <p id="mobile_id_auth_countdown"></p>
</div>
<div>
  <button type="button" onclick="mobileIdAuth()">Authenticate</button>
</div>

<script>

  function mobileIdAuth() {
    function setStatus(status) {
      document.getElementById('mobile_id_auth_status').innerText = status;
    }
    var swMid = new SignWiseMobileId({lang: 'et'});
    var data = {
      ssn: document.getElementById("mobile_id_auth_ssn").value,
      msisdn: document.getElementById("mobile_id_auth_msisdn").value
    };
    swMid.authenticate("mobile-id-ajax.php?auth", data, function(err, result) {
      if (err) {
        return setStatus('Starting authentication failed');
      }
      if (!result.verificationCode) {
        return setStatus("Failure reason: " + result.status);
      }
      setStatus('An SMS was sent with the verification code ' + result.verificationCode + '. If it matches, please enter your mobile-ID PIN1 code on your phone');
      swMid.authenticationResult("mobile-id-ajax.php?auth_result", function(secondsRemaining) {
        document.getElementById("mobile_id_auth_countdown").innerText = secondsRemaining === false ? '' : secondsRemaining;
      }, function(err, result) {
        if (err) {
          setStatus("Error getting result");
        } else if (result.status === "OK") {
          setStatus("Success");
          location.reload();
        } else {
          setStatus("Failure reason: " + result.status);
        }
      });
    });
  }
</script>