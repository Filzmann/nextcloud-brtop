import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runJavaScriptSuite } from '../../localbase/tests/Support/js-runner.mjs';

const root = dirname(dirname(fileURLToPath(import.meta.url)));

runJavaScriptSuite({
    root,
    testFiles: [
        'tests/js/agenda-editor-smoke.js',
        'tests/js/admin-access-smoke.js',
        'tests/js/absence-review-smoke.js',
        'tests/js/meeting-components-smoke.js',
        'tests/js/model-smoke.js',
        'tests/js/meeting-repository-smoke.js',
        'tests/js/legislature-repository-smoke.js',
        'tests/js/legislature-editor-smoke.js',
        'tests/js/protocol-editor-smoke.js',
        'tests/js/top-form-router-smoke.js',
        'tests/js/ui-smoke.js',
    ],
    successMessage: 'BRTop JavaScript tests passed',
});
