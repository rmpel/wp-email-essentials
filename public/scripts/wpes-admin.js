/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/scripts/wpes-admin.js":
/*!**************************************!*\
  !*** ./assets/scripts/wpes-admin.js ***!
  \**************************************/
/***/ (() => {

jQuery(document).ready(function ($) {
  if ($('.wpes-settings').length > 0) {
    /**
     * Settings panel
     */
    var keys = 'enable_history,smtp-enabled,enable-smime,enable-dkim,smtp-is_html'.split(',');
    keys.forEach(function (selector) {
      $('#' + selector).on('change', function (e) {
        // we need 'function' here for 'this'.
        var target_id = e.target.id;
        $('.on-' + target_id).toggle($(this).is(':checked'));
        $('.not-' + target_id).toggle(!$(this).is(':checked'));
      }).trigger('change');
    });
    var preventInfinite = false;
    $('.on-regexp-test').each(function () {
      // we need 'function' here for 'this'.
      (function (field, regexp, label) {
        $('#' + field).on('change keyup blur paste', function () {
          var value = $(this).val() || null;

          if ($(this).is('[type=checkbox],[type=radio]')) {
            if (!preventInfinite) {
              preventInfinite = true;
              var name = $(this).attr('name');
              var siblings = $(this).closest('.postbox').find('[name="' + name + '"]').not(this);
              siblings.trigger('change');
              preventInfinite = false;
            }

            if (!$(this).is(':checked')) {
              value = null;
            }
          }

          label.toggle(null !== (value || '').match(new RegExp(regexp, 'i')));
        }).trigger('change');
      })($(this).attr('data-field'), $(this).attr('data-regexp'), $(this));
    });
  }

  if ($('.wpes-emails').length > 0) {
    /**
     * Emails panel
     */
    $('.email-item').on('click', function (e) {
      // we need 'function' here for 'this'.
      if ($(e.target).is('a.dashicons-download')) {
        e.stopPropagation();
        return true;
      }

      $(this).addClass('active').siblings().removeClass('active').removeClass(function (index, className) {
        return (className.match(/(^|\s)show-\S+/g) || []).join(' ');
      });
      var id = '#' + $('.email-item.active').attr('id').replace('email-', 'email-data-');
      var that = $(id);
      $('#mail-data-viewer .email-data').removeClass(function (index, className) {
        return (className.match(/(^|\s)show-\S+/g) || []).join(' ');
      }); // Click to cycle through the views.

      var this_and_that = $(this).add(that);

      if ($(this).is('.show-body')) {
        this_and_that.removeClass('show-body').addClass('show-headers');
      } else if ($(this).is('.show-headers')) {
        this_and_that.removeClass('show-headers').addClass('show-alt-body');
      } else if ($(this).is('.show-alt-body')) {
        this_and_that.removeClass('show-alt-body').addClass('show-debug');
      } else {
        this_and_that.addClass('show-body');
      }

      $(window).trigger('resize');
    });
    $(window).bind('resize', function () {
      // we need 'function' here for 'this'.
      $('.autofit').each(function () {
        $(this).css('width', $(this).parent().innerWidth());
        $(this).css('height', $(this).parent().innerHeight());
      });
    }).trigger('resize');
  }

  if ($('.wpes-admins').length > 0) {
    /**
     * Admins panel
     */
    var t = function t() {
      // we need 'function' here for 'this'.
      if (/^\/[\s\S]+\/[i]?$/.test($(this).val() || '')) {
        var that = this;
        var re = $(that).val();
        re = re.split(re.substr(0, 1));
        re = new RegExp(re[1], re[2]);
        $('.a-fail').each(function () {
          $(this).toggleClass('match', re.test($(this).text() || ''));
        });
      } else {
        $('.a-fail').removeClass('match');
      }
    };

    $('.a-regexp').bind('blur', function () {
      // we need 'function' here for 'this'.
      var val = $(this).val() || '';

      if ('' === val) {
        return $(this).removeClass('error match');
      }

      $(this).toggleClass('error', !/^\/[\s\S]+\/[i]?$/.test(val)).not('.error').addClass('match');
    }).bind('focus', function (e) {
      // we need 'function' here for 'this'.
      $('.a-fail,.a-regexp').removeClass('match');
      $(this).removeClass('error match');
      t.apply(this, [e]);
    }).bind('keyup', t);
  }
});

/***/ }),

/***/ "./assets/styles/wpes-admin.scss":
/*!***************************************!*\
  !*** ./assets/styles/wpes-admin.scss ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/scripts/wpes-admin": 0,
/******/ 			"styles/wpes-admin": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunkwp_email_essentials"] = self["webpackChunkwp_email_essentials"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["styles/wpes-admin"], () => (__webpack_require__("./assets/scripts/wpes-admin.js")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["styles/wpes-admin"], () => (__webpack_require__("./assets/styles/wpes-admin.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=wpes-admin.js.map