import { spawnSync } from 'node:child_process';
import { readdirSync, statSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = dirname(dirname(fileURLToPath(import.meta.url)));

function collectJsFiles(directory) {
    const path = join(root, directory);
    const files = [];

    function walk(currentPath) {
        for (const entry of readdirSync(currentPath)) {
            const entryPath = join(currentPath, entry);
            const stats = statSync(entryPath);

            if (stats.isDirectory()) {
                walk(entryPath);
                continue;
            }

            if (stats.isFile() && entryPath.endsWith('.js')) {
                files.push(relative(root, entryPath));
            }
        }
    }

    walk(path);
    files.sort();

    return files;
}

function run(command, args) {
    console.log(`> ${[command, ...args].join(' ')}`);
    const result = spawnSync(command, args, {
        cwd: root,
        stdio: 'inherit',
    });

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

for (const file of collectJsFiles('js')) {
    run('node', ['--check', file]);
}

for (const file of [
    'tests/js/model-smoke.js',
    'tests/js/meeting-repository-smoke.js',
    'tests/js/ui-smoke.js',
]) {
    run('node', [file]);
}

console.log('BRTop JavaScript tests passed');
