/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/idb-keyval/dist/esm/index.js":
/*!***************************************************!*\
  !*** ./node_modules/idb-keyval/dist/esm/index.js ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "clear": function() { return /* binding */ clear; },
/* harmony export */   "createStore": function() { return /* binding */ createStore; },
/* harmony export */   "del": function() { return /* binding */ del; },
/* harmony export */   "entries": function() { return /* binding */ entries; },
/* harmony export */   "get": function() { return /* binding */ get; },
/* harmony export */   "getMany": function() { return /* binding */ getMany; },
/* harmony export */   "keys": function() { return /* binding */ keys; },
/* harmony export */   "promisifyRequest": function() { return /* binding */ promisifyRequest; },
/* harmony export */   "set": function() { return /* binding */ set; },
/* harmony export */   "setMany": function() { return /* binding */ setMany; },
/* harmony export */   "update": function() { return /* binding */ update; },
/* harmony export */   "values": function() { return /* binding */ values; }
/* harmony export */ });
/* harmony import */ var safari_14_idb_fix__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! safari-14-idb-fix */ "./node_modules/safari-14-idb-fix/dist/esm/index.js");


function promisifyRequest(request) {
    return new Promise((resolve, reject) => {
        // @ts-ignore - file size hacks
        request.oncomplete = request.onsuccess = () => resolve(request.result);
        // @ts-ignore - file size hacks
        request.onabort = request.onerror = () => reject(request.error);
    });
}
function createStore(dbName, storeName) {
    const dbp = (0,safari_14_idb_fix__WEBPACK_IMPORTED_MODULE_0__["default"])().then(() => {
        const request = indexedDB.open(dbName);
        request.onupgradeneeded = () => request.result.createObjectStore(storeName);
        return promisifyRequest(request);
    });
    return (txMode, callback) => dbp.then((db) => callback(db.transaction(storeName, txMode).objectStore(storeName)));
}
let defaultGetStoreFunc;
function defaultGetStore() {
    if (!defaultGetStoreFunc) {
        defaultGetStoreFunc = createStore('keyval-store', 'keyval');
    }
    return defaultGetStoreFunc;
}
/**
 * Get a value by its key.
 *
 * @param key
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function get(key, customStore = defaultGetStore()) {
    return customStore('readonly', (store) => promisifyRequest(store.get(key)));
}
/**
 * Set a value with a key.
 *
 * @param key
 * @param value
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function set(key, value, customStore = defaultGetStore()) {
    return customStore('readwrite', (store) => {
        store.put(value, key);
        return promisifyRequest(store.transaction);
    });
}
/**
 * Set multiple values at once. This is faster than calling set() multiple times.
 * It's also atomic – if one of the pairs can't be added, none will be added.
 *
 * @param entries Array of entries, where each entry is an array of `[key, value]`.
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function setMany(entries, customStore = defaultGetStore()) {
    return customStore('readwrite', (store) => {
        entries.forEach((entry) => store.put(entry[1], entry[0]));
        return promisifyRequest(store.transaction);
    });
}
/**
 * Get multiple values by their keys
 *
 * @param keys
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function getMany(keys, customStore = defaultGetStore()) {
    return customStore('readonly', (store) => Promise.all(keys.map((key) => promisifyRequest(store.get(key)))));
}
/**
 * Update a value. This lets you see the old value and update it as an atomic operation.
 *
 * @param key
 * @param updater A callback that takes the old value and returns a new value.
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function update(key, updater, customStore = defaultGetStore()) {
    return customStore('readwrite', (store) => 
    // Need to create the promise manually.
    // If I try to chain promises, the transaction closes in browsers
    // that use a promise polyfill (IE10/11).
    new Promise((resolve, reject) => {
        store.get(key).onsuccess = function () {
            try {
                store.put(updater(this.result), key);
                resolve(promisifyRequest(store.transaction));
            }
            catch (err) {
                reject(err);
            }
        };
    }));
}
/**
 * Delete a particular key from the store.
 *
 * @param key
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function del(key, customStore = defaultGetStore()) {
    return customStore('readwrite', (store) => {
        store.delete(key);
        return promisifyRequest(store.transaction);
    });
}
/**
 * Clear all values in the store.
 *
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function clear(customStore = defaultGetStore()) {
    return customStore('readwrite', (store) => {
        store.clear();
        return promisifyRequest(store.transaction);
    });
}
function eachCursor(customStore, callback) {
    return customStore('readonly', (store) => {
        // This would be store.getAllKeys(), but it isn't supported by Edge or Safari.
        // And openKeyCursor isn't supported by Safari.
        store.openCursor().onsuccess = function () {
            if (!this.result)
                return;
            callback(this.result);
            this.result.continue();
        };
        return promisifyRequest(store.transaction);
    });
}
/**
 * Get all keys in the store.
 *
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function keys(customStore = defaultGetStore()) {
    const items = [];
    return eachCursor(customStore, (cursor) => items.push(cursor.key)).then(() => items);
}
/**
 * Get all values in the store.
 *
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function values(customStore = defaultGetStore()) {
    const items = [];
    return eachCursor(customStore, (cursor) => items.push(cursor.value)).then(() => items);
}
/**
 * Get all entries in the store. Each entry is an array of `[key, value]`.
 *
 * @param customStore Method to get a custom store. Use with caution (see the docs).
 */
function entries(customStore = defaultGetStore()) {
    const items = [];
    return eachCursor(customStore, (cursor) => items.push([cursor.key, cursor.value])).then(() => items);
}




/***/ }),

/***/ "./node_modules/safari-14-idb-fix/dist/esm/index.js":
/*!**********************************************************!*\
  !*** ./node_modules/safari-14-idb-fix/dist/esm/index.js ***!
  \**********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/**
 * https://bugs.webkit.org/show_bug.cgi?id=226547
 * Safari has a horrible bug where IDB requests can hang while the browser is starting up.
 * The only solution is to keep nudging it until it's awake.
 * This probably creates garbage, but garbage is better than totally failing.
 */
function idbReady() {
    const isSafari = !navigator.userAgentData &&
        /Safari\//.test(navigator.userAgent) &&
        !/Chrom(e|ium)\//.test(navigator.userAgent);
    // No point putting other browsers or older versions of Safari through this mess.
    if (!isSafari || !indexedDB.databases)
        return Promise.resolve();
    let intervalId;
    return new Promise((resolve) => {
        const tryIdb = () => indexedDB.databases().finally(resolve);
        intervalId = setInterval(tryIdb, 100);
        tryIdb();
    }).finally(() => clearInterval(intervalId));
}

/* harmony default export */ __webpack_exports__["default"] = (idbReady);


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
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!************************************!*\
  !*** ./assets/js/webworkers/db.js ***!
  \************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var idb_keyval__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! idb-keyval */ "./node_modules/idb-keyval/dist/esm/index.js");

const store = (0,idb_keyval__WEBPACK_IMPORTED_MODULE_0__.createStore)('easy_media_manager', 'media_manager');
onmessage = e => {
  let {
    type,
    key,
    val
  } = e.data;
  switch (type) {
    case 'get':
      (0,idb_keyval__WEBPACK_IMPORTED_MODULE_0__.get)(key, store).then(res => postMessage(res));
      break;
    case 'set':
      (0,idb_keyval__WEBPACK_IMPORTED_MODULE_0__.set)(key, val, store).then(() => postMessage(true)).catch(() => postMessage(false));
      break;
    case 'del':
      (0,idb_keyval__WEBPACK_IMPORTED_MODULE_0__.del)(key, store).then(() => postMessage(true)).catch(() => postMessage(false));
      break;
    case 'clr':
      (0,idb_keyval__WEBPACK_IMPORTED_MODULE_0__.clear)(store).then(() => postMessage(true)).catch(() => postMessage(false));
      break;
    case 'keys':
      (0,idb_keyval__WEBPACK_IMPORTED_MODULE_0__.keys)(store).then(res => postMessage(res)).catch(() => postMessage(false));
      break;
  }
};
}();
/******/ })()
;