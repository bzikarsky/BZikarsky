<?php

namespace BZikarsky\Process;

/**
 * Process is a default implementation for running any callable in a subprocess
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) 2010 Benjamin Zikarsky
 * @license   <http://opensource.org/licenses/bsd-license.php> New BSD
 */
class Process extends ProcessAbstract 
{
    /**
     * The callable to be run in the subprocess
     *
     * @var mixed
     */
	protected $callable = null;

    /**
     * Constructor - takes and saves the callable
     *
     * @param mixed $callable
     */
	public function __construct($callable)
	{
		if (!is_callable($callable)) {
			throw new \InvalidArgumentException("Expected callable");
		}
		
		$this->callable = $callable;
	}
	
	/**
	 * Execute the callable and return the exit-code
	 *
	 * @return int
	 */
	protected function execute()
	{
		return intval(call_user_func($this->callable, $this));
	}
}

