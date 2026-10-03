const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

const source = fs.readFileSync('js/modules/persistent-horizontal-scroll.js', 'utf8');
const listeners = new Map();
const target = {
    scrollWidth: 1200,
    clientWidth: 600,
    scrollLeft: 0,
    getBoundingClientRect: () => ({ top: 40, bottom: 500, left: 20, width: 600 }),
    addEventListener: (type, listener) => listeners.set(`target:${type}`, listener),
    removeEventListener: (type) => listeners.delete(`target:${type}`),
};
const spacer = { style: {} };
const track = {
    hidden: true,
    scrollLeft: 0,
    style: {},
    classList: { toggle: () => {} },
    querySelector: () => spacer,
    addEventListener: (type, listener) => listeners.set(`track:${type}`, listener),
    removeEventListener: (type) => listeners.delete(`track:${type}`),
};
const appRoot = {
    getBoundingClientRect: () => ({ top: 0, bottom: 700, left: 0, width: 900 }),
    querySelector: (selector) => selector === '[data-persistent-horizontal-scroll-track]' ? track : null,
    querySelectorAll: (selector) => selector === '[data-persistent-horizontal-scroll]' ? [target] : [],
    addEventListener: (type, listener) => listeners.set(`root:${type}`, listener),
    removeEventListener: (type) => listeners.delete(`root:${type}`),
};
const windowListeners = new Map();
const context = {
    window: {
        innerHeight: 800,
        addEventListener: (type, listener) => windowListeners.set(type, listener),
        removeEventListener: (type) => windowListeners.delete(type),
    },
    MutationObserver: class { observe() {} disconnect() {} },
};
context.window.MutationObserver = context.MutationObserver;
vm.runInNewContext(source, context);

const cleanup = context.window.BRTop.persistentHorizontalScroll.bind(appRoot);
assert.strictEqual(track.hidden, false);
assert.strictEqual(spacer.style.width, '1200px');
target.scrollLeft = 240;
listeners.get('target:scroll')();
assert.strictEqual(track.scrollLeft, 240);
track.scrollLeft = 80;
listeners.get('track:scroll')();
assert.strictEqual(target.scrollLeft, 80);
cleanup();
assert.strictEqual(listeners.has('target:scroll'), false);

console.log('BRTop persistent horizontal scroll smoke test passed');
