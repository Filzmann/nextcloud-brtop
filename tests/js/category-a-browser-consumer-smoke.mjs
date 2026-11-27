import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const appRoot = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const bundleRoot = resolve(appRoot, 'js/shared');
const requiredAssets = [
    'api-client.js',
    'model.js',
    'overlay.js',
    'repository.js',
    'ui.js',
    'LICENSE',
    'flz-shared.lock.json',
];

for (const asset of requiredAssets) {
    if (!existsSync(resolve(bundleRoot, asset))) {
        throw new Error(`Das app-lokale Kategorie-A-Artefakt fehlt: js/shared/${asset}`);
    }
}

const lock = JSON.parse(readFileSync(resolve(bundleRoot, 'flz-shared.lock.json'), 'utf8'));
if (lock.schema !== 1
    || lock.component !== 'browser'
    || lock.app_id !== 'brtop'
    || lock.app_namespace !== 'BrTop'
    || lock.browser_global !== 'window.OCA.BrTop.Shared'
    || lock.packages?.browser?.name !== '@filzmann/shared-browser'
    || lock.packages?.browser?.version !== '0.1.0'
    || !/^[a-f0-9]{64}$/.test(lock.packages?.browser?.sha256 || '')
    || Object.keys(lock.packages || {}).join(',') !== 'browser') {
    throw new Error('Der Browser-Lock beschreibt nicht ausschließlich das isolierte brtop-Bundle.');
}

const license = readFileSync(resolve(bundleRoot, 'LICENSE'), 'utf8');
if (!license.includes('SPDX-License-Identifier: AGPL-3.0-or-later')
    || !license.includes('GNU Affero General Public License')) {
    throw new Error('Die gebündelten Lizenzmetadaten fehlen.');
}

const indexTemplate = readFileSync(resolve(appRoot, 'templates/index.php'), 'utf8');
const adminTemplate = readFileSync(resolve(appRoot, 'templates/admin.php'), 'utf8');
for (const asset of ['api-client', 'model', 'overlay', 'repository', 'ui']) {
    if (!indexTemplate.includes(`script('brtop', 'shared/${asset}')`)) {
        throw new Error(`Das Haupttemplate lädt shared/${asset} nicht app-lokal.`);
    }
}
if (!adminTemplate.includes("script('brtop', 'shared/api-client')")) {
    throw new Error('Das Admintemplate lädt shared/api-client nicht app-lokal.');
}

const loadOrder = new Map([
    ['shared/api-client', 'modules/api'],
    ['shared/ui', 'modules/ui'],
    ['shared/overlay', 'admin-access'],
    ['shared/model', 'models/protocol-block'],
    ['shared/repository', 'repositories/meeting-repository'],
]);
for (const [sharedAsset, consumerAsset] of loadOrder) {
    const sharedPosition = indexTemplate.indexOf(sharedAsset);
    const consumerPosition = indexTemplate.indexOf(consumerAsset);
    if (sharedPosition < 0 || consumerPosition < 0 || sharedPosition > consumerPosition) {
        throw new Error(`${sharedAsset} wird nicht vor ${consumerAsset} geladen.`);
    }
}
if (adminTemplate.indexOf('shared/api-client') > adminTemplate.indexOf('modules/api')) {
    throw new Error('Das Admintemplate lädt shared/api-client nicht vor modules/api.');
}

const forbiddenReferences = [
    'window.LocalBase.api',
    'window.LocalBase.models',
    'window.LocalBase.repositories',
    'window.LocalBase.ui',
    "script('localbase', 'api/",
    "script('localbase', 'models/",
    "script('localbase', 'repositories/",
    "script('localbase', 'ui/",
    '/../flz_shared/',
    '/../../flz_shared/',
];
for (const relativeRoot of ['js', 'templates']) {
    for (const file of sourceFiles(resolve(appRoot, relativeRoot))) {
        const source = readFileSync(file, 'utf8');
        for (const forbidden of forbiddenReferences) {
            if (source.includes(forbidden)) {
                throw new Error(`${file.slice(appRoot.length + 1)} enthält weiterhin ${forbidden}.`);
            }
        }
    }
}

const requests = [];
let responseStatus = 200;
let responseBody;
const noticeElement = { className: '', hidden: true, textContent: '' };
const window = {
    fetch: async (url, options) => {
        requests.push({ url, options });
        return {
            ok: responseStatus >= 200 && responseStatus < 300,
            status: responseStatus,
            text: async () => responseBody ?? (url.endsWith('/api/state')
                ? JSON.stringify({ meetings: [{ id: 7, title: 'BR-Sitzung' }], settings: {} })
                : JSON.stringify({ ok: true })),
        };
    },
};
window.window = window;
window.OC = {
    generateUrl: path => `/index.php${path}`,
    requestToken: 'brtop-token',
};

const context = vm.createContext({
    document: { getElementById: id => id === 'brtop-notice' ? noticeElement : null },
    encodeURIComponent,
    Error,
    JSON,
    Math,
    Number,
    Object,
    OC: window.OC,
    String,
    window,
});

for (const relative of [
    'js/shared/api-client.js',
    'js/shared/model.js',
    'js/shared/overlay.js',
    'js/shared/repository.js',
    'js/shared/ui.js',
    'js/modules/api.js',
    'js/modules/ui.js',
    'js/models/protocol-block.js',
    'js/models/generated-document.js',
    'js/models/agenda-item.js',
    'js/models/meeting.js',
    'js/repositories/meeting-repository.js',
    'js/repositories/legislature-repository.js',
])
{
    const filename = resolve(appRoot, relative);
    vm.runInContext(readFileSync(filename, 'utf8'), context, { filename });
}

const shared = window.OCA?.BrTop?.Shared;
if (!shared?.api?.ApiClient
    || !shared?.models?.Model
    || !shared?.repositories?.Repository
    || !shared?.ui?.Notice
    || !shared?.ui?.Overlay
    || !shared?.ui?.AdminAccessOverlay) {
    throw new Error('Das app-lokale Browser-Bundle stellt seine Primitives nicht vollständig isoliert bereit.');
}
if ('LocalBase' in window) {
    throw new Error('Das app-lokale Browser-Bundle erzeugt weiterhin window.LocalBase.');
}

const repository = new window.BRTop.repositories.MeetingRepository(window.BRTop.api);
const state = await repository.state();
await repository.createNextRegular();
if (!(state.meetings[0] instanceof window.BRTop.models.Meeting)
    || requests.length !== 2
    || requests[0].url !== '/index.php/apps/brtop/api/state'
    || requests[0].options.headers.requesttoken !== 'brtop-token'
    || requests[1].url !== '/index.php/apps/brtop/api/meetings/next-regular'
    || requests[1].options.method !== 'POST') {
    throw new Error('ApiClient, Model und MeetingRepository bewahren URL-, CSRF- und Schreibvertrag nicht gemeinsam.');
}

// Existing error responses must remain failures through the app adapter.
for (const [body, expectedMessage] of [
    [JSON.stringify({ ok: false, message: 'Nicht erlaubt' }), 'Nicht erlaubt'],
    ['<html>Forbidden</html>', 'HTTP 403'],
])
{
    responseStatus = 403;
    responseBody = body;
    const previousRequestCount = requests.length;
    let failure;
    try
    {
        await repository.createNextRegular();
    }
    catch (error)
    {
        failure = error;
    }
    if (!failure
        || failure.message !== expectedMessage
        || failure.status !== 403
        || requests.length !== previousRequestCount + 1)
    {
        throw new Error('Eine verweigerte API-Antwort wurde nicht als einzelner fehlgeschlagener Aufruf weitergegeben.');
    }
}

responseStatus = 200;
responseBody = '';
if (Object.keys(await window.BRTop.api.request('/api/empty')).length !== 0)
{
    throw new Error('Eine leere erfolgreiche API-Antwort wurde nicht als leeres Ergebnis gelesen.');
}

if (window.BRTop.ui.esc('<Sitzung & mehr>') !== '&lt;Sitzung &amp; mehr&gt;') {
    throw new Error('Das app-lokale UI-Primitive bewahrt den Escaping-Vertrag nicht.');
}
window.BRTop.ui.showNotice('Bereit', 'success');
if (noticeElement.textContent !== 'Bereit'
    || noticeElement.hidden
    || noticeElement.className !== 'brtop-notice brtop-notice-success') {
    throw new Error('Die gebündelte Notice bewahrt ihren sichtbaren Erfolgszustand nicht.');
}

console.log('BRTop isolated Category-A browser consumer smoke passed.');

function sourceFiles(root) {
    const files = [];
    for (const entry of readdirSync(root, { withFileTypes: true })) {
        const path = resolve(root, entry.name);
        if (entry.isDirectory()) {
            files.push(...sourceFiles(path));
        } else if (entry.isFile()) {
            files.push(path);
        }
    }
    return files;
}
