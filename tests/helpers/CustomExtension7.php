<?php

declare(strict_types = 1);

namespace Adeira\Tests;

use Tester\FileMock;

// wrong extend test
class CustomExtension7 extends \Nette\DI\CompilerExtension
{

    public function provideConfig()
    {
        $config = <<<CONFIG
services:
	- Adeira\Tests\Service3(@named(), %%numericExtensionParameter%%, '%%', %%arrayKey.arrayValue%%)
	- implement: Adeira\Tests\IService5Factory
	  arguments:
	  	- test
	  	- %%numericExtensionParameter%%
	  	- %%falseExtensionParameter%%
	  	- %%nullExtensionParameter%%
	named: Adeira\Tests\Service2
CONFIG;
        return FileMock::create($config, 'neon');
    }

}
