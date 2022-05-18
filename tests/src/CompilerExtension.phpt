<?php

declare(strict_types = 1);

namespace Adeira\Tests;

use Nette;
use Tester;
use Tester\Assert;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class CompilerExtension extends \Tester\TestCase
{

	/** @var \Nette\DI\Compiler */
	private $compiler;

	/** @var \Nette\DI\Container */
	private $generatedContainer;

	public function setUp()
	{
		Tester\Helpers::purge($tempDir = __DIR__ . '/../temp/thread_' . getenv(Tester\Environment::THREAD));

		$configurator = new Nette\Configurator;
		$configurator->defaultExtensions['extensions'] = [\Adeira\ConfigurableExtensionsExtension::class, [TRUE]];
		$config = ['%debugMode%', ['%appDir%'], '%tempDir%/cache'];
		$configurator->defaultExtensions['application'] = [Nette\Bridges\ApplicationDI\ApplicationExtension::class, $config];
		$configurator->defaultExtensions['http'] = [Nette\Bridges\HttpDI\HttpExtension::class, ['%consoleMode%']];
		$configurator->defaultExtensions['latte'] = [Nette\Bridges\ApplicationDI\LatteExtension::class, ['%tempDir%/cache/latte', '%debugMode%']];
		$configurator->defaultExtensions['routing'] = [Nette\Bridges\ApplicationDI\RoutingExtension::class, ['%debugMode%']];
		$configurator->setTempDirectory($tempDir);
		$configurator->addConfig(__DIR__ . '/config.neon');
		$configurator->onCompile[] = function (Nette\Configurator $sender, Nette\DI\Compiler $compiler) {
			$this->compiler = $compiler;
		};
		$dic = $configurator->createContainer();
		$this->generatedContainer = $dic;
	}

	/**
	 * Original parameters are added in config.neon:
	 *
	 * parameters:
	 *     k1: v1
	 *     k2: v2
	 *
	 * These parameters are overridden by CustomExtension1 using addConfig method.
	 */
	public function testAddConfigParameters()
	{
		$parameters = $this->generatedContainer->getParameters();
		Assert::same('v1', $parameters['k1']);
		Assert::same('overridden', $parameters['k2']);
		Assert::same('v3', $parameters['k3']);
	}

	public function testExtensionParametersExpand()
	{
		//there is test in constructor of Service3
		$this->generatedContainer->getByType(\Adeira\Tests\Service3::class);
		//do not add another asserts so it will fail when the test forgets to execute an assertion
	}

	public function testExtensionParametersExpandFactory()
	{
		//there is test in constructor of Service5
		$this->generatedContainer->getByType(\Adeira\Tests\IService5Factory::class)->create();
		//do not add another asserts so it will fail when the test forgets to execute an assertion
	}

	public function testAddConfigExtensions()
	{
		Assert::same([
			'services' => 'Nette\DI\Extensions\ServicesExtension',
			'parameters' => 'Nette\DI\Extensions\ParametersExtension',
			'application' => 'Nette\Bridges\ApplicationDI\ApplicationExtension',
			'constants' => 'Nette\Bootstrap\Extensions\ConstantsExtension',
			'search' => 'Nette\DI\Extensions\SearchExtension',
			'decorator' => 'Nette\DI\Extensions\DecoratorExtension',
			'di' => 'Nette\DI\Extensions\DIExtension',
			'extensions' => 'Adeira\ConfigurableExtensionsExtension',
			'http' => 'Nette\Bridges\HttpDI\HttpExtension',
			'latte' => 'Nette\Bridges\ApplicationDI\LatteExtension',
			'php' => 'Nette\Bootstrap\Extensions\PhpExtension',
			'routing' => 'Nette\Bridges\ApplicationDI\RoutingExtension',
			'session' => 'Nette\Bridges\HttpDI\SessionExtension',
			'ext1' => 'Adeira\Tests\CustomExtension1',
			'ext2' => 'Adeira\Tests\CustomExtension2',
			'ext3' => 'Adeira\Tests\ExtensionEmptyConfig',
			'ext4' => 'Adeira\Tests\CustomExtension4',
			'inject' => 'Nette\DI\Extensions\InjectExtension',
		], array_map(function ($item) {
			return get_class($item);
		}, $this->compiler->getExtensions()));

		/** @var CustomExtension2 $extension */
		$extension = $this->compiler->getExtensions('Adeira\Tests\CustomExtension2')['ext2'];
		Assert::same([
			'ek1' => 'ev1',
			'ek2' => 'overridden',
			'ek3' => 'ev3',
		], $extension->getConfig());
	}

	public function testAddConfigServices()
	{
		$builder = $this->compiler->getContainerBuilder();
		Assert::same(
			[
				'Nette\DI\Container',
				'Nette\Application\Application',
				'Nette\Application\IPresenterFactory',
				'Nette\Application\LinkGenerator',
				'Nette\Http\RequestFactory',
				'Nette\Http\Request',
				'Nette\Http\Response',
				'Nette\Bridges\ApplicationLatte\LatteFactory',
				'Nette\Bridges\ApplicationLatte\TemplateFactory',
				'Nette\Http\Session',
				'Adeira\Tests\CommandsStack',
				'Adeira\Tests\Definition',
				'Adeira\Tests\Service4', //registered in config.neon
				'Adeira\Tests\Service2', //overridden (named service)
				'Nette\Routing\Router',
				'Adeira\Tests\Service3', //registered later in extension (reregistered after extension parameters eval)
				'Adeira\Tests\IService5Factory',  //registered later in extension (reregistered after extension parameters eval)
			],
		array_map(function (Nette\DI\Definitions\Definition $item) {
			return $item->getType();
		}, array_values($builder->getDefinitions())));
	}

	public function testSetMapping()
	{
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = $this->generatedContainer->getService('application.presenterFactory');
		Assert::type('Nette\Application\PresenterFactory', $presenterFactory);

		$reflectionClass = new \ReflectionClass($presenterFactory);
		$reflectionProperty = $reflectionClass->getProperty('mapping');
		$reflectionProperty->setAccessible(TRUE);
		Assert::same([
			'*' => ['a\\', '*b\\', '*c'],
			'Nette' => ['NetteModule\\', '*\\', '*Presenter'],
			'Module' => ['App\\', '*Module\\', 'Controllers\\*Controller'],
		], $reflectionProperty->getValue($presenterFactory));
	}

	public function testRegisteredCommands()
	{
		$stack = $this->generatedContainer->getService('ext1.commands.stack');
		Assert::same([
			'com1_ext1',
			'com2_ext1',
			'com3_ext1',
			'com1_ext2',
			'com2_ext2',
		], $stack->commands);
	}

}

(new CompilerExtension)->run();
