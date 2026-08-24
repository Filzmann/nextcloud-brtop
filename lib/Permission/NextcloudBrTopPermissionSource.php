<?php
declare(strict_types=1);
namespace OCA\BrTop\Permission;
use OCA\BrTop\Service\BrGroupsService;
final class NextcloudBrTopPermissionSource implements BrTopPermissionSourceInterface{public function __construct(private BrGroupsService $groups){}public function memberGroupId():string{return $this->groups->memberGroupName();}}
