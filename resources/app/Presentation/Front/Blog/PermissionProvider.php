<?php

declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use Drago\Permission\Provider;
use Drago\Permission\Role;
use Nette\Security\Permission;


final class PermissionProvider implements Provider
{
	private const string Resource = 'Front:Blog';


	public function register(Permission $acl): void
	{
		$acl->addResource(self::Resource);
		$acl->allow(Role::RoleGuest, self::Resource, 'blog-read');
		$acl->allow(Role::RoleGuest, self::Resource, 'blog-view');
		$acl->allow(Role::RoleUser, self::Resource, 'blog-comment');
	}
}
