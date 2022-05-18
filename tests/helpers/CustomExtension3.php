<?php

declare(strict_types = 1);

namespace Adeira\Tests;

use Tester\FileMock;

class CustomExtension3 extends \Adeira\CompilerExtension
{

	public function provideConfig()
	{
		$config = <<<CONFIG
services:
	- Adeira\Tests\Service3('a', %%thisExtensionParameterDoesNotExist%%, 'c', 1)

CONFIG;
		return FileMock::create($config, 'neon');
	}

}
