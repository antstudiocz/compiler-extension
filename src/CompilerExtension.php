<?php

declare(strict_types = 1);

namespace Adeira;

use Nette\Application\IPresenterFactory;
use Nette\DI\Definitions\Statement;

class CompilerExtension extends \Nette\DI\CompilerExtension
{

	/** @var array */
	protected $servicesToResolve = [];

	public function provideConfig()
	{
	}

	/**
	 * @deprecated Use \Adeira\ConfigurableExtensionsExtension instead.
	 */
	protected function addConfig($configFile)
	{
		trigger_error(__METHOD__ . ' is deprecated. Use ' . \Adeira\ConfigurableExtensionsExtension::class . ' instead.');
		return $this->loadFromFile($configFile);
	}

	/**
	 * Should be called in beforeCompile().
	 *
	 * @param array $mapping ['Articles' => 'Ant\Articles\Presenters\*Presenter']
	 */
	protected function setMapping(array $mapping)
	{
		$builder = $this->getContainerBuilder();
		$presenterFactory = $builder->getByType(IPresenterFactory::class);
		if ($presenterFactory === NULL) {
			throw new \Nette\InvalidStateException('Cannot find Nette\Application\IPresenterFactory implementation.');
		}
		$builder->getDefinition($presenterFactory)->addSetup('setMapping', [$mapping]);
	}

	public function beforeCompile()
	{
		/** @var Statement $definition */
		foreach ($this->servicesToResolve as $definition) {
			$definition = ConfigurableExtensionsExtension::expand($definition, (array)$this->config);
			$this->loadDefinitionsFromConfig([$definition]);
		}
	}

	public function addDefinitionToResolve($name, $definition)
	{
		$this->servicesToResolve[$name] = $definition;
	}

}
