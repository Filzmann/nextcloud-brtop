<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migrationPath = $root . '/lib/Migration/Version000010Date202610220001.php';
if (!is_file($migrationPath)) {
    throw new RuntimeException('Die additive Dokumentvorlagen-Migration fehlt.');
}
$migration = (string)file_get_contents($migrationPath);
foreach ([
    'brt_doc_templates',
    'template_key',
    'version_no',
    'format',
    'content',
    'status',
    'created_at',
    'approved_at',
    'template_version',
] as $contract) {
    if (!str_contains($migration, $contract)) {
        throw new RuntimeException("Dokumentvorlagen-Migrationsvertrag fehlt: {$contract}");
    }
}
foreach (['hasTable', 'hasColumn', 'addUniqueIndex'] as $guard) {
    if (!str_contains($migration, $guard)) {
        throw new RuntimeException("Dokumentvorlagen-Migration besitzt keinen erforderlichen Wiederholschutz: {$guard}");
    }
}
if (!str_contains($migration, "'length' => 16777215")) {
    throw new RuntimeException('Die ODT-Vorlage benötigt ein portables MEDIUMTEXT-Limit statt eines 64-KB-TEXT-Felds.');
}
$repository = (string)file_get_contents($root . '/lib/Repository/DocumentTemplateRepository.php');
foreach (['base64_encode', 'base64_decode'] as $binaryContract) {
    if (!str_contains($repository, $binaryContract)) {
        throw new RuntimeException('Binäre ODT-Vorlagen werden nicht portabel gespeichert: ' . $binaryContract);
    }
}

$info = (string)file_get_contents($root . '/appinfo/info.xml');
if (!str_contains($info, '<version>0.1.35</version>')) {
    throw new RuntimeException('Die neue Schema-Migration benötigt App-Version 0.1.35.');
}

echo "DocumentTemplateMigrationContractTest: OK\n";
