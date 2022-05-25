<?php

declare(strict_types = 1);

namespace Adeira\Tests;

class CustomExtension6 extends \Adeira\CompilerExtension
{

	public function provideConfig()
	{
		return ['ext' => ['tadá' => 'tudum']];
	}

}
