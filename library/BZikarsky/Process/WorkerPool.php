<?php

namespace BZikarsky\Process;

/**
 * A pool of workers which can execute a distributed workload
 *
 * The WorkerPool takes a callable and an array of data, which is then
 * distributed to several php processes and executed
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) 2010 Benjamin Zikarsky
 * @license   <http://opensource.org/licenses/bsd-license.php> New BSD
 */
class WorkerPool
{
	/**
	 * Worker callable
	 *
	 * @var mixed
	 */
	protected $worker = null;
	
	/**
	 * Processes-Queue
	 *
	 * @var \Bzikarsky\Process\Queue
	 */
	protected $queue = null;
	
	/**
	 * Constructor
	 *
	 * @param mixed   $worker      a callable which performs the work
	 * @param array   $tasks       each element is one work-package and passed
	 *                             to the worker
	 * @param integer $maxWorkers  maximum number of workers
	 */
	public function __construct($worker, array $work=array(), $maxWorkers=20)
	{
        // setup queue
        $this->queue  = new Queue($maxWorkers);
        
        // check worker
        if (!is_callable($worker)) {
        	throw new \InvalidArgumentException("Worker must be callable");
        } else {
        	$this->worker = $worker;
        }
        
        // add work
        foreach ($work as $w) {
            $this->addWork($w);
        }
	}
	
	/**
	 * Starts execution
	 *
	 * @param boolean $block If set to true, the execution blocks until all
	 *                       work is done, default: false
	 * @return \BZikarsky\Process\WorkerPool
	 */
	public function start($block=false)
	{
	   	$this->queue->start();
	   	
	   	if ($block) {
	   		$this->wait();
	   	}
	   	
	   	return $this;
	}
	
	/**
	 * Blocks until the work is done
	 *
	 * @return \BZikarsky\Process\WorkerPool
	 */
	public function wait()
	{
		$this->queue->wait();
		return $this;
	}
	
	/**
	 * Adds a work-package
	 *
	 * @param mixed $work
	 * @return \BZikarsky\Process\WorkerPool
	 */
	public function addWork($work)
	{
		$worker = $this->worker;
		$this->queue->insert(new Process(
            function() use ($worker, $work) 
            {
                return call_user_func($worker, $work);
	        }
        ));
        
        return $this;
	}
	
	/**
	 * Returns the queue, which runs the worker-processes
	 *
	 * @return \BZikarsky\Process\Queue
	 */
	public function getQueue()
	{
		return $this->queue;
	}
}