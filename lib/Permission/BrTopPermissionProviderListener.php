<?php
declare(strict_types=1);
namespace OCA\BrTop\Permission;
use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
final class BrTopPermissionProviderListener{public function __construct(private BrTopPermissionProvider $provider){}public function handle(object $event):void{if($event instanceof RegisterPermissionProvidersEvent)$event->register($this->provider);}}
