<?php

declare(strict_types=1);

namespace Psr\Clock { if (!interface_exists(ClockInterface::class)) { interface ClockInterface { public function now(): \DateTimeImmutable; } } }
namespace Psr\Log { if (!interface_exists(LoggerInterface::class)) { interface LoggerInterface { public function emergency(string|\Stringable $message,array $context=[]):void;public function alert(string|\Stringable $message,array $context=[]):void;public function critical(string|\Stringable $message,array $context=[]):void;public function error(string|\Stringable $message,array $context=[]):void;public function warning(string|\Stringable $message,array $context=[]):void;public function notice(string|\Stringable $message,array $context=[]):void;public function info(string|\Stringable $message,array $context=[]):void;public function debug(string|\Stringable $message,array $context=[]):void;public function log($level,string|\Stringable $message,array $context=[]):void; } } }
namespace OCP {
    if (!interface_exists(IUser::class)) { interface IUser { public function getUID(): string; } }
    if (!interface_exists(IUserSession::class)) { interface IUserSession { public function getUser(): ?IUser; } }
    if (!interface_exists(IGroupManager::class)) { interface IGroupManager { public function isAdmin(string $uid): bool; public function get(string $gid); } }
}
namespace OCP\AppFramework\Utility { if (!interface_exists(ITimeFactory::class)) { interface ITimeFactory extends \Psr\Clock\ClockInterface { public function getTime():int;public function getDateTime(string $time='now',?\DateTimeZone $timezone=null):\DateTime;public function withTimeZone(\DateTimeZone $timezone):static;public function getTimeZone(?string $timezone=null):\DateTimeZone; } } }
namespace OCA\BrTop\Repository { if (!interface_exists(TemporaryAdminAccessRepositoryInterface::class)) { interface TemporaryAdminAccessRepositoryInterface { public function replaceActive(string $targetUid,string $grantedBy,\DateTimeImmutable $startsAt,\DateTimeImmutable $endsAt):array;public function revokeActive(string $targetUid,string $revokedBy,\DateTimeImmutable $revokedAt):bool;public function activeFor(string $targetUid,\DateTimeImmutable $at):?array;public function history():array; } } }
namespace OCA\BrTop\Tests {
    use OCA\BrTop\Repository\TemporaryAdminAccessRepositoryInterface;
    use OCA\BrTop\Service\BrAdminAccessManagerPolicy;
    use OCA\BrTop\Service\TemporaryAdminAccessDeniedException;
    use OCA\BrTop\Service\TemporaryAdminAccessService;

    $actorUid = 'chair';
    $session = new class($actorUid) implements \OCP\IUserSession {
        public function __construct(public string &$uid) {}
        public function getUser(): ?\OCP\IUser { return new class($this->uid) implements \OCP\IUser { public function __construct(private string $uid){} public function getUID():string{return $this->uid;} }; }
    };
    $groups = new class implements \OCP\IGroupManager {
        public function isAdmin(string $uid): bool { return $uid === 'admin-target'; }
        public function get(string $gid): mixed { return null; }
    };
    $repository = new class implements TemporaryAdminAccessRepositoryInterface {
        public int $mutations = 0;
        public array $rows = [];
        public function replaceActive(string $targetUid,string $grantedBy,\DateTimeImmutable $startsAt,\DateTimeImmutable $endsAt):array{$this->mutations++;return $this->rows[]=['id'=>1,'targetUid'=>$targetUid,'grantedBy'=>$grantedBy,'startsAt'=>$startsAt,'endsAt'=>$endsAt,'revokedAt'=>null,'revokedBy'=>null];}
        public function revokeActive(string $targetUid,string $revokedBy,\DateTimeImmutable $revokedAt):bool{$this->mutations++;return true;}
        public function activeFor(string $targetUid,\DateTimeImmutable $at):?array{return null;}
        public function history():array{return $this->rows;}
        public function historyForUid(string $uid,int $limit):array{return array_slice($this->rows,0,$limit);}
    };
    $clock = new class implements \OCP\AppFramework\Utility\ITimeFactory { public function now():\DateTimeImmutable{return new \DateTimeImmutable('2026-11-01T10:00:00+00:00');}public function getTime():int{return$this->now()->getTimestamp();}public function getDateTime(string $time='now',?\DateTimeZone $timezone=null):\DateTime{return new \DateTime($time,$timezone);}public function withTimeZone(\DateTimeZone $timezone):static{return$this;}public function getTimeZone(?string $timezone=null):\DateTimeZone{return new \DateTimeZone($timezone??'UTC');} };
    $logger = new class implements \Psr\Log\LoggerInterface { public function emergency(string|\Stringable $m,array $c=[]):void{}public function alert(string|\Stringable $m,array $c=[]):void{}public function critical(string|\Stringable $m,array $c=[]):void{}public function error(string|\Stringable $m,array $c=[]):void{}public function warning(string|\Stringable $m,array $c=[]):void{}public function notice(string|\Stringable $m,array $c=[]):void{}public function info(string|\Stringable $m,array $c=[]):void{}public function debug(string|\Stringable $m,array $c=[]):void{}public function log($l,string|\Stringable $m,array $c=[]):void{} };
    $policy = new class extends BrAdminAccessManagerPolicy { public array $allowed=['chair','br-dpo']; public function __construct(){} public function canManage(string $uid):bool{return in_array($uid,$this->allowed,true);} };
    $service = new TemporaryAdminAccessService($session,$groups,$repository,$clock,$logger,$policy);

    assertSameValue(true, $service->canManage(), 'BR-Vorsitz muss Adminfreigaben verwalten dürfen.');
    $grant = $service->activate('admin-target', 60);
    assertSameValue('chair', $grant['grantedBy'], 'Freigabe muss den BR-Akteur revisionsfähig festhalten.');
    $actorUid = 'br-dpo';
    assertSameValue(true, $service->canManage(), 'Explizit berechtigtes BR-Mitglied muss Adminfreigaben verwalten dürfen.');
    $service->state();
    $actorUid = 'member';
    $before = $repository->mutations;
    assertThrows(static fn() => $service->activate('admin-target', 60), TemporaryAdminAccessDeniedException::class, 'BR-Mitglied ohne BT6 muss abgewiesen werden.');
    assertSameValue($before, $repository->mutations, 'Abgewiesene Freigabe darf die Historie nicht verändern.');

    echo "BRTop temporary admin BR role access tests passed\n";
}
