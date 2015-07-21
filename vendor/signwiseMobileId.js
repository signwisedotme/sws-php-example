"use strict";

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

/* Version 1.0.0 */

/**
 * SignWise Mobile ID SDK
 *
 * Possible error codes:
 *   TIMEOUT
 *   MOBILE_PHONE_PERSONAL_CODE_MISMATCH
 *   INVALID_MID_PARAMETERS
 *   MID_NOT_ACTIVATED
 *   MOBILE_LOGIN_FAILED
 *   VALIDATION_ERROR
 *   UNKNOWN_ERROR
 *   USER_CANCEL
 *   INTERNAL_ERROR
 *   MID_NOT_READY
 *   SENDING_ERROR
 *   SIM_ERROR
 *   PHONE_ABSENT
 */

(function () {

  function SignWiseMobileId(opts) {
    if (!(this instanceof SignWiseMobileId)) {
      return new SignWiseMobileId(opts);
    }
    opts = opts || {};
    this.lang = opts.lang;
    this.countdownMax = opts.countdownMax || 60; // Countdown length in seconds
    this.pollingDelay = opts.pollingDelay || 15000; // Time to wait until first result polling request in milliseconds
    this.pollingInterval = opts.pollingInterval || 2000; // Interval between result polling requests in milliseconds
  }

  var _proto = SignWiseMobileId.prototype;

  _proto.authenticate = function(url, data, cb) {
    this._initOperation("auth", url, data, cb);
  };

  _proto.sign = function(url, data, cb) {
    this._initOperation("sign", url, data, cb);
  };

  _proto.authenticationResult = function(url, countdownCb, cb) {
    this._result("auth", url, countdownCb, cb);
  };

  _proto.signingResult = function(url, countdownCb, cb) {
    this._result("sign", url, countdownCb, cb);
  };

  _proto._initOperation = function(operation, url, data, cb) {
    if (!data.lang) {
      data.lang = this.lang;
    }
    this._ajaxLoad(url, data, function(err, result) {
      if (err) {
        return cb(err);
      }
      if (!result.verificationCode) {
        result.status = parseResultError(result);
        return cb(false, result);
      }
      cb(false, result);
    });
  };

  _proto._result = function(operation, url, countdownCb, cb) {
    var self = this;
    var countdown = this.countdownMax;
    var timedOut = false;
    var countdownInterval = setInterval(function() {
      if (--countdown <= 0) {
        timedOut = true;
        countdownCb(false);
        cb({status: "TIMEOUT"});
        clearCountdown();
      } else {
        countdownCb(countdown);
      }
    }, 1000);

    function clearCountdown() {
      clearInterval(countdownInterval);
      countdownCb(false);
    }

    function expectResult() {
      self._ajaxLoad(url, function(err, result) {
        if (timedOut) {
        } else if (err) {
          // AJAX/network error
          cb(err);
          clearCountdown();
        } else if (result.error) {
          // Request produced server error
          clearCountdown();
          cb(result.error.msg ? result.error.msg : "Unexpected Mobile ID error");
        } else if (result.status === "OK") {
          // Positive response received
          clearCountdown();
          cb(false, result);
        } else if (result.status === "PENDING") {
          // No response yet, keep polling
          setTimeout(expectResult, self.pollingInterval);
        } else if (result.status || result.code) {
          // Negative response received
          clearCountdown();
          result.status = parseResultError(result);
          cb(false, result);
        }
      });
    }
    setTimeout(expectResult, this.pollingDelay);
  };

  function parseResultError(result) {
    if (result.status) {
      return result.status;
    } else if (result.reason && result.reason.errorCode) {
      return result.reason.errorCode;
    } else if (result.code === 400) {
      return "VALIDATION_ERROR";
    } else {
      return "UNKNOWN_ERROR";
    }
  }

  _proto._ajaxLoad = function(url, data, callback) {
    if (typeof callback === "undefined" && typeof data === "function") {
      callback = data;
      data = {};
    }
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function ensureReadiness() {
      if (xhr.readyState < 4) {
        return;
      }
      if (xhr.status !== 200) {
        callback("xhr_failed");
        return;
      }
      if (xhr.readyState === 4) {
        try {
          var parsedResponse = JSON.parse(xhr.responseText);
          callback(false, parsedResponse);
        } catch(ex) {
          console.log("CANNOT PARSE JSON", ex, "RESP", xhr.responseText);
          callback("invalid_json");
        }
      }
    };
    xhr.open("POST", url);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.send(JSON.stringify(data));
  };

  window.SignWiseMobileId = SignWiseMobileId;
}());