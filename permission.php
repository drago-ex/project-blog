<?php

declare(strict_types=1);

/**
 * Basic access rules of this package for drago-ex/permission.
 * Generated into the project by: php vendor/bin/create-permission --packages
 */
return [
	// Registers the blog administration; administrators are allowed everything.
	[
		'className' => 'PermissionProvider',
		'namespace' => 'App\Presentation\Backend\Blog',
		'resource' => 'Backend:Blog',
		'outputDir' => 'app/Presentation/Backend/Blog',
		'allow' => ['RoleAdmin'],
	],
	// Everyone may read the published articles.
	[
		'className' => 'PermissionProvider',
		'namespace' => 'App\Presentation\Front\Blog',
		'resource' => 'Front:Blog',
		'outputDir' => 'app/Presentation/Front/Blog',
		'allow' => ['RoleGuest,self::Resource'],
	],
];
