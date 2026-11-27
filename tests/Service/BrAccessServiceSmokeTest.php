<?php

declare(strict_types=1);

namespace OCA\BrTop\Tests {

    use OCA\BrTop\Exception\AccessDeniedException;
    use OCA\BrTop\Service\BrAccessService;
    use OCA\BrTop\Service\BrPermissionService;

    $permissions = new class extends BrPermissionService {
        public function __construct() {}
        public function has(string $uid,string $permission):bool{return $uid==='br-member'||($uid==='admin'&&$this->active);}
        public function assert(string $uid,string $permission):void{if(!$this->has($uid,$permission))throw new AccessDeniedException('Keine Berechtigung.');}
        public bool $active=false;
    };
    $service = new BrAccessService($permissions);

    assertSameValue(false, $service->canUse('admin'), 'Native Nextcloud admins must not receive BRTop access without an app-local grant.');
    $permissions->active=true;
    assertSameValue(true, $service->canUse('admin'), 'An active app-local grant should enable BRTop admin access.');
    assertSameValue(true, $service->canUse('br-member'), 'Members of the configured BR group should be allowed to use BRTop.');
    assertSameValue(false, $service->canUse('other-user'), 'Unrelated authenticated users must be denied.');
    assertThrows(
        static fn() => $service->assertCanUse('other-user'),
        AccessDeniedException::class,
        'The service must deny unrelated users server-side.'
    );
    assertThrows(
        static fn() => $service->assertPermission('other-user', BrPermissionService::LEGISLATURE_MANAGE),
        AccessDeniedException::class,
        'Legislature management must remain restricted to holders of the separate capability.'
    );

    echo 'BrAccessService smoke tests passed' . PHP_EOL;
}
