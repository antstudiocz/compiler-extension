<?php

declare(strict_types = 1);

namespace Adeira\Tests;

use Tester\Assert;
use Tester\FileMock;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class BadExtensionParent extends \Tester\TestCase
{

	public function testBadParent()
	{
		$compiler = new \Nette\DI\Compiler;
		$compiler->addExtension('extensions', new \Adeira\ConfigurableExtensionsExtension);
		$config = <<<NEON
extensions:
	ext7: Adeira\Tests\CustomExtension7
NEON;
		$compiler->loadConfig(FileMock::create($config, 'neon'));
		Assert::throws(
			function () use ($compiler) {
				$compiler->compile();
			},
			\BadMethodCallException::class
		);
	}

}

(new BadExtensionParent)->run();
