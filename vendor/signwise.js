'use strict';

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

/* Version 1.1.2

Usage:

<script type="text/javascript" src="signwise.js"></script>
...
var swPlugin = new SignWisePlugin({lang: 'et', com: pluginCommunication});
* example input:
    pluginCommunication optional object {url: '/path/to/plugin/communication', logErrors: true, callback: function(error, result) {}}
  pluginCommunication.callback gets passed an error string on failure: "xhr_failed" or "invalid_json"
  pluginCommunication.callback gets passed an object on success {pluginVersionStatus: ...}
  pluginVersionStatus can be integer with one of the following values:
    50 - Critical, must update
    40 - Warning, should update
    30 - Card ATR not supported in installed version, must update to use
    20 - Card ATR not supported in latest version, contact customer support
    10 - OK
  Critically outdated plugin usage is prohibited if using correctly configured pluginCommunication.

function callback(error, result) {}

swPlugin.getSupportedHashTypes(callback);
* example result: array ["SHA1", "SHA224", "SHA256"]

swPlugin.getAuthCertificates(callback);
* example result: array ["308204bb308203a..."]

swPlugin.getSignCertificates(callback);
* example result: array ["308204753082035..."]

swPlugin.authenticate(challenge, callback);
* example input:
    challenge: string "0123456789abcdef0123456789abcdef01234567"
* example result: string "5a74d11d1bc1ad4ba32df03fc9ad0159c22b4a38..."

swPlugin.sign(documentHash, hashType, callback);
* example input:
    documentHash string "abcdef0123456789abcdef0123456789abcdef0123456789ab"
    hashType string "SHA256"
* example result: string "25018bb3b3fc31e235f1f63f86520fe4d0a393..."

swPlugin.getVersion(callback);
* example result: string "1.10.4.1"

Error handling
  Errors are returned in the following form:
  {"code":"unknown_card","details":"4|0|Unknown card in reader, ATR: 3b9f96803fc7008031e073fe211b6407720c0082900098"}
  "details" is only added in case of error codes "unknown_card" and "technical_error".

Error codes:
  no_reader - No card reader was found
  no_card - No card was found in the card reader
  unknown_card - An unknown card was inserted
  user_cancel_pin - User cancelled PIN input
  user_cancel_cert - User denied certificate request
  technical_error - Unknown error
  plugin_outdated - Plugin is critically outdated
    * occurs only when "com" is specified at initialization.

*/

if (!Array.prototype.map) {
  Array.prototype.map = function(fun /*, thisArg */) {
    "use strict";
    if (this === void 0 || this === null) throw new TypeError();
    var t = Object(this);
    var len = t.length >>> 0;
    if (typeof fun !== "function") throw new TypeError();
    var res = new Array(len);
    var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
    for (var i = 0; i < len; i++) { if (i in t) res[i] = fun.call(thisArg, t[i], i, t); }
    return res;
  };
}

// Base64 encoder [https://gist.github.com/999166] by [https://github.com/nignag]
if (typeof btoa === 'undefined' ) {
  var btoa = function (input) {
    var str = String(input);
    function InvalidCharacterError(message) { this.message = message; }
    InvalidCharacterError.prototype = new Error;
    InvalidCharacterError.prototype.name = 'InvalidCharacterError';
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
    for (
      var block, charCode, idx = 0, map = chars, output = '';
      str.charAt(idx | 0) || (map = '=', idx % 1);
      output += map.charAt(63 & block >> 8 - idx % 1 * 8)
      ) {
      charCode = str.charCodeAt(idx += 3/4);
      if (charCode > 0xFF) {
        throw new InvalidCharacterError("'btoa' failed: The string to be encoded contains characters outside of the Latin1 range.");
      }
      block = block << 8 | charCode;
    }
    return output;
  }
}

if (!Array.prototype.indexOf) {
  Array.prototype.indexOf = function(searchElement, fromIndex) {
    var k; if (this == null) { throw new TypeError('"this" is null or not defined'); }
    var O = Object(this); var len = O.length >>> 0; if (len === 0) { return -1; }
    var n = +fromIndex || 0; if (Math.abs(n) === Infinity) { n = 0; }
    if (n >= len) { return -1; } k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);
    while (k < len) { if (k in O && O[k] === searchElement) { return k; } k++; }
    return -1;
  };
}

(function () {

  var chromeExtensionProtocol = {
    authenticate: ['languageCode', 'challenge'],
    getAuthCertificates: ['languageCode'],
    getSignCertificates: ['languageCode'],
    getSupportedHashTypes: [],
    getVersion: [],
    sign: ['languageCode', 'documentHash', 'hashType'],
    signMass: ['languageCode', 'documentHashes', 'hashType']
  };

  var waitingForExtensions = true;
  var cachedVersionStatuses = {};
  setTimeout(function() { waitingForExtensions = false; }, 1000);

  function SignWisePlugin(opts, callback) {
    if(!(this instanceof SignWisePlugin)) {
      return new SignWisePlugin(opts);
    }
    this.id = Math.random().toString(36).substr(2);
    this.lang = opts.lang;
    this.com = opts.com;
    var availableCertFormats = ['base64', 'hex'];
    this.certFormat = (!opts.certFormat || (-1 === availableCertFormats.indexOf(opts.certFormat)))
      ? availableCertFormats[0] : opts.certFormat;
  }

  function hex2char(hex) {
    hex = hex.match(/[0-9a-f]{2}/igm);
    if (!hex) { return ""; }

    hex = hex.map(function(el){
      return String.fromCharCode(parseInt(el,16));
    });
    return hex.join("");
  }

  function hex2b64(hex) {
    return btoa(hex2char(hex));
  }

  function checkNavigatorPlugin(mimeType) {
    if (!navigator || !navigator.plugins) {
      return false;
    }
    for (var i = 0; i < navigator.plugins.length; i++) {
      if (navigator.plugins[i]['0'] && navigator.plugins[i]['0'].type === mimeType) {
        return true;
      }
    }
    return false;
  }

  var _proto = SignWisePlugin.prototype;

  _proto._ajaxLoad = function(url, data, callback) {
    var cacheResponse = false;
    if (typeof callback === 'undefined' && typeof data === 'function') {
      callback = data;
      data = {};
      var cacheKey = this._version + (this.isChromeExtension ? 'c' : '');
      if (cachedVersionStatuses.hasOwnProperty(cacheKey)) {
        callback(false, cachedVersionStatuses[cacheKey], true);
        return;
      }
      cacheResponse = true;
    }
    var xhr = new XMLHttpRequest();
    data.version = this._version;
    data.isChromeExtension = this.isChromeExtension || false;
    data.lang = this.lang;
    data.ua = navigator.userAgent;
    xhr.onreadystatechange = function ensureReadiness() {
      if (xhr.readyState < 4) {
        return;
      }
      if (xhr.status !== 200) {
        callback('xhr_failed');
        return;
      }
      if (xhr.readyState === 4) {
        try {
          var parsedResponse = JSON.parse(xhr.responseText);
          if (cacheResponse && parsedResponse.pluginVersionStatus) {
            cachedVersionStatuses[cacheKey] = parsedResponse;
          }
          callback(false, parsedResponse);
        } catch(ex) {
          callback("invalid_json");
        }
      }
    };
    xhr.open("POST", url);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.send(JSON.stringify(data));
  };

  _proto.init = function (callback, isProbe, tryRegular) {
    tryRegular = typeof tryRegular === 'undefined' ? false : tryRegular;
    var self = this;
    if ((self._loaded || isProbe) && self.reGet()) {
      callback(false, true);
      return;
    }
    if (isProbe) {
      callback(false, false);
      return;
    }
    var isChrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
    var waitForChromeExtension = isChrome && (typeof SignWiseChromePlugin === 'undefined') && waitingForExtensions;
    if (waitForChromeExtension) { // Chrome extension takes some time to become available after pageload
      var i = 0;
      var intervalWaitForChromeExtension = setInterval(function() {
        i ++;
        if (typeof SignWiseChromePlugin !== 'undefined' || i == 20) {
          clearInterval(intervalWaitForChromeExtension);
          tryPlugins();
        }
      }, 50);
    } else {
      tryPlugins();
    }
    function tryPlugins() {
      if (isChrome) {
        try {
          self._object = tryRegular ? new SignWiseChromePlugin() : new SignWiseChromePluginMass();
          self.isChromeExtension = true;
        } catch (ex) {
        }
      }
      if (!self.isChromeExtension && !self.reGet()) {
        var mime = "application/x-signwiseplugin" + (tryRegular ? '' : 'mass');
        if (!isChrome || checkNavigatorPlugin(mime)) {
          var objectTag = '<object id="' + self.id + '" type="' + mime + '" style="width: 1px; height: 1px; position: absolute; visibility: hidden;"></object>';
          var div = document.createElement("div");
          div.setAttribute("id", "pluginLocation" + self.id);
          document.body.appendChild(div);
          document.getElementById("pluginLocation" + self.id).innerHTML = objectTag;
          self.reGet();
        }
      }
      setTimeout(function() {
        self.canConnect(function(error, isLoaded) {
          if (isLoaded) {
            self.isMassPlugin = !tryRegular;
          } else if (!tryRegular) {
            self.id = Math.random().toString(36).substr(2);
            self.init(callback, isProbe, true);
            return;
          }
          if (isLoaded && self.com) {
            self._ajaxLoad(self.com.url, function(xhrError, parsedResponse, isCachedResponse) {
              if (xhrError) {
                self.com.callback(xhrError);
                // Plugin call is executed if communication is unavailable
                callback(error, isLoaded);
              } else {
                if (parsedResponse && parsedResponse.pluginVersionStatus >= 50) {
                  // Plugin call is only refused if plugin is critically outdated
                  self._loaded = false;
                  self._version = false;
                  callback({code: 'plugin_outdated'});
                } else {
                  callback(error, isLoaded);
                }
                if (!isCachedResponse) {
                  self.com.callback(false, parsedResponse);
                }
              }
            });
          } else {
            callback(error, isLoaded);
          }
        });
      }, 0);
    }
  };

  _proto.toCertFormat = function(data) {
    if (this.certFormat === 'base64') {
      data = hex2b64(data);
      var lineLength = 64;
      var result = "-----BEGIN CERTIFICATE-----\n";
      for (var i = 0; i < data.length; i += lineLength) {
        result += i >= data.length - lineLength ? data.substring(i) : data.substring(i, i + lineLength) + "\n";
      }
      result += "\n-----END CERTIFICATE-----";
      return result;
    } else {
      return data;
    }
  };

  _proto.reGet = function() {
    if (!this.isChromeExtension) {
      this._object = document.getElementById(this.id);
    }
    return this._object;
  };

  _proto.canConnect = function (cb) {
    var self = this;
    this.getVersion(function(error, version) {
      if (error) {
        cb(error);
        return;
      }
      self._loaded = !!version;
      self._version = version;
      cb(false, self._loaded);
    }, true);
  };

  _proto._makePluginCallback = function(command, args, cb) {
    var self = this;
    return function(error, result) {
      var cmdSplitResults = ['getAuthCertificates', 'getAuthenticationCertificate', 'getSignCertificates', 'getSigningCertificate', 'getSupportedHashTypes'];
      if ((-1 !== cmdSplitResults.indexOf(command)) && !error && (typeof result === 'string')) {
        if (command === 'getSupportedHashTypes') {
          result = result.toUpperCase().split(',');
        } else {
          result = result.split(';');
          for (var i = 0; i < result.length; i++) {
            result[i] = self.toCertFormat(result[i]);
          }
        }
      }
      if ((error instanceof TypeError) && command === 'getAuthCertificates') {
        self._run('getAuthenticationCertificate', [], cb);
        return;
      }
      if ((error instanceof TypeError) && command === 'getSignCertificates') {
        self._run('getSigningCertificate', [], cb);
        return;
      }
      var parsedError = self._parseError(error);
      if (parsedError) {
        self.logErrorToServer(parsedError);
      }
      cb(parsedError, result);
    }
  };

  _proto.logErrorToServer = function(error) {
    var self = this;
    if (!self.com || !self.com.logErrors) {
      return;
    }
    self._ajaxLoad(self.com.url, {errorCode: error.code, errorDetails: error.details}, self.com.callback);
  };

  _proto._parseError = function(error) {
    if (!error) {
      return false;
    }
    var errorMapping = {
      'No card in reader': 'no_card',
      'Unknown card in reader, ATR': 'unknown_card',
      'The user did not input a PIN.': 'user_cancel_pin',
      'User canceled PIN input': 'user_cancel_pin',
      'The user did not allow a certificate request.': 'user_cancel_cert',
      'Reader is unavailable.': 'no_reader'
    };
    var result;
    for (var i in errorMapping) {
      if (errorMapping.hasOwnProperty(i) && (-1 !== error.indexOf(i))) {
        result = {code: errorMapping[i]};
        break;
      }
    }
    if (!result) {
      result = {code: 'technical_error'};
    }
    if (-1 !== ['technical_error', 'unknown_card'].indexOf(result.code)) {
      result.details = error;
    }
    return result;
  };

  _proto._run = function (command, args, cb, isProbe) {
    var self = this;
    this.init(function(error, isLoaded) {
      if (!isLoaded) {
        cb({code: 'no_plugin'});
        return;
      }
      var internalCallback = self._makePluginCallback(command, args, cb);
      if (self.isChromeExtension) {
        if (!(args instanceof Array)) {
          args = [];
        }
        if (typeof self._object.fn === 'function') {
          // New Chrome extension
          var opts = {command: command};
          for (var i = 0; i < args.length; i++) {
            opts[chromeExtensionProtocol[command][i]] = args[i];
          }
          self._object.fn(opts, internalCallback);
          return;
        } else if (command === 'getVersion') {
          command = 'getNativeMessagingHostVersion';
        }
        args.push(internalCallback);
      } else if (command === 'getVersion') {
        internalCallback(false, self._object && self._object.version);
        return;
      }
      try {
        var res = args ? self._object[command].apply(self._object, args) : self._object[command]();
      } catch (ex) {
        internalCallback(ex, false);
        return;
      }
      if (!self.isChromeExtension) {
        // The plugin does not trigger the callback. We must do it ourselves, though it is synchronous now.
        if (res) {
          internalCallback(false, self._object.result);
        } else {
          internalCallback(self._object.result, false);
        }
      }
    }, isProbe || false);
  };

  _proto.getSupportedHashTypes = function (cb) {
    this._run('getSupportedHashTypes', [], cb);
  };

  _proto.getAuthCertificates = function (cb) {
    this._run('getAuthCertificates', [this.lang], cb);
  };

  _proto.getSignCertificates = function (cb) {
    this._run('getSignCertificates', [this.lang], cb);
  };

  _proto.authenticate = function (challenge, cb) {
    this._run('authenticate', [this.lang, challenge], cb);
  };

  _proto.getVersion = function (cb, isProbe) {
    this._run('getVersion', [], cb, isProbe);
  };

  _proto.sign = function (documentHash, hashType, cb) {
    var params = [this.lang, documentHash];
    if (typeof hashType === 'string') {
      params.push(hashType.toLowerCase());
    }
    this._run('sign', params, cb);
  };

  _proto.signMass = function (documentHashes, hashType, cb) {
    var params = [this.lang, documentHashes];
    if (typeof hashType === 'string') {
      params.push(hashType.toLowerCase());
    }
    this._run('signMass', params, cb);
  };

  window.SignWisePlugin = SignWisePlugin;
}());