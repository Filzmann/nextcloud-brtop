<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$template = (string)file_get_contents($root . '/templates/index.php');
$page = (string)file_get_contents($root . '/lib/Controller/PageController.php');
$main = (string)file_get_contents($root . '/js/main.js');
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$controller = is_file($root . '/lib/Controller/BrDpoAssignmentController.php') ? (string)file_get_contents($root . '/lib/Controller/BrDpoAssignmentController.php') : '';

foreach (['role="tablist"', 'role="tab"', 'role="tabpanel"', 'aria-controls=', 'aria-selected=', 'Einstellungen'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Semantischer BRTop-Einstellungstab fehlt: {$contract}");
}
foreach (['brtop-br-dpo-member-select', '<select', 'multiple', 'BR-Datenschutzberechtigung'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Auswahlmenü für BR-Datenschutzberechtigung fehlt: {$contract}");
}
if (preg_match('/brtop-br-dpo-member[^>]*<input[^>]+type=["\']text/', $template) === 1) {
    throw new RuntimeException('BR-Datenschutzberechtigungen dürfen nicht über Freitexteingaben vergeben werden.');
}
foreach (['canManageSettings', 'canAssignBrDpo', 'canAccessSettings'] as $contract) {
    if (!str_contains($page . $template, $contract)) throw new RuntimeException("Serverseitige Einstellungsprojektion fehlt: {$contract}");
}
foreach (['br_dpo_assignment#state', 'br_dpo_assignment#save'] as $route) {
    if (!str_contains($routes, $route)) throw new RuntimeException("BR-Datenschutzroute fehlt: {$route}");
}
foreach (['NoAdminRequired', 'NoCSRFRequired', 'memberUidsJson'] as $contract) {
    if (!str_contains($controller, $contract)) throw new RuntimeException("Controllervertrag für BR-Datenschutzberechtigung fehlt: {$contract}");
}
if (!str_contains($main, "byId('review-absences').hidden = !capabilities.canConfirmAbsence;")) {
    throw new RuntimeException('Die Abwesenheitsaktion muss das eigene BT5-Recht statt des Legislaturrechts projizieren.');
}

echo "BRTop permission settings accessibility tests passed\n";
