<?php
declare(strict_types=1);
namespace OCA\BrTop\Permission;
use OCA\BrTop\Service\BrRoleService;
final class NextcloudBrTopPermissionSource implements BrTopPermissionSourceInterface{
 public function __construct(private BrRoleService $roles){}
 public function memberGroupId():?string{return $this->roles->memberGroupId();}
 public function chairGroupId():?string{return $this->roles->chairGroupId();}
 public function deputyGroupId():?string{return $this->roles->deputyGroupId();}
}
