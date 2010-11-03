<?php

namespace BZikarsky\Process;

/**
 * Queue implements a priority queue for an unlimited amount of subprocesses
 *
 * Queue wraps around a SplPriorityQueue and executes a limited or unlimited 
 * number of tasks at the same time
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) 2010 Benjamin Zikarsky
 * @license   <http://opensource.org/licenses/bsd-license.php> New BSD
 */
class Queue
{
	/**
	 * Block until all jobs have finished
	 */
	const FLAG_BLOCK = 1;
	
	/**
	 * Start as soon as jobs become avaialable
	 */
	const FLAG_AUTOSTART = 2;
	
	/**
	 * Disable sempahore locks
	 */
	const FLAG_NO_SEMAPHORE = 4;
	
	/**
     * Queued jobs
   	 *
	 * @var \SplPriorityQueue
 	 */
	protected $jobs = null;

	/**
     * List of running jobs
	 *
	 * @var array
	 */
	protected $active = array();
	
	/**
	 * Maximum number of forks
	 * 
	 * @var integer
	 */
	protected $limit = 0;
	
	/**
	 * Queue flags
	 * 
	 * @var integer
	 */
	protected $flags = 0;
	
	/**
	 * Semaphore for job adding and syncing
	 * 
	 * @var resource
	 */
	protected $semaphore = null;
	
	/**
	 * Class constructor
	 * 
	 * @param integer $limit
	 * @param integer $mode
	 */
	public function __construct($limit=0, $flags=0)
	{
		// setup job queue
		$this->jobs = new \SplPriorityQueue();
		$this->jobs->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
		
		// setup semaphore
		$this->semaphore = sem_get(
			// unique semaphore for each object
            hexdec(crc32(spl_object_hash($this)))
		);
		
		// set state
		$this->setLimit($limit);
		$this->setFlags($flags);

		// remember pid to be able to determine master-process
		$this->pid = posix_getpid();
	}
	
	/**
	 * Callback which is called as soon a child-process exits
	 *
	 * @param \BZikarsky\Process\ProcessInterface $job
	 * @param integer $status
	 */
	public function jobExited(ProcessInterface $job , $status)
	{
		// ignore in case it's not our child process
		if (!array_key_exists($job->getPid(), $this->active)) {
			return;
		}
		
		$useSemaphore = !(self::FLAG_NO_SEMAPHORE & $this->flags); 
        
        if ($useSemaphore) {
            sem_acquire($this->getSemaphore());
        };
		// remove the pid form the active pool
		unset($this->active[$job->getPid()]);
        if ($useSemaphore) {
            sem_release($this->getSemaphore());
        }
		
		// trigger execution of queued processes
		$this->execute();
	}
	
	/**
	 * Executes as many as possible queued jobs
	 */
	protected function execute()
	{
		$useSemaphore = !(self::FLAG_NO_SEMAPHORE & $this->flags); 
		
		// run new jobs as long there are any and the queue does not hit the 
		// limit
		while (count($this->jobs) > 0 && !$this->isMaxed()) {
			// extract job and register callback
			$job = $this->jobs->extract();
			$job->addEventListener('exit', array($this, 'jobExited'));
			
			// activate and remember
			if ($useSemaphore) {
				sem_acquire($this->getSemaphore());
			}
			$job->run();
			$this->active[$job->getPid()] = $job;
			if ($useSemaphore) {
	            sem_release($this->getSemaphore());
	        }
		}
	}
	
    /**
     * Add a job to the queue with an optional priority
     * 
     * @param mixed $job
     * @param int   $priority
     * @return \BZikarsky\Process\Queue
     */
	public function insert($job, $priority=0)
	{
		// allow callables by wrapping them in the default
		// job class
		if (!$job instanceof ProcessInterface) {
			
			if (!is_callable($job)) {
				throw new \InvalidArgumentException("Instance of JobInterface"
				    . " or callable expected");
			}
			
			$job = new Job($job);
		}
		
		// add to queue
		$this->jobs->insert($job, $priority);
		
		// trigger execution if the queue is already being executed
		if ($this->flags & self::FLAG_AUTOSTART || $this->isActive()) {
			$this->execute();
		}
		
		return $this;
	}
	
	/**
	 * Start jobs
	 * 
	 * @param boolean $block when true, the methods blocks until all jobs are 
	 *                       finished, defaults to false
	 * @return \BZikarsky\Process\Queue
	 */
	public function start($block=false)
	{
		$this->execute();
		
		// wait for jobs to finish if requested
		if ($this->flags & self::FLAG_BLOCK || $block) {
			$this->wait();
		}
		
		return $this;
	}
	
	/**
	 * Waits for all jobs to finish
	 *
	 * @return \BZikarsky\Process\Queue
	 */
	public function wait()
	{
		while ($this->isActive()) {
			Controller::getInstance()->wait();			
		}
		
		return $this;
	}
	
	/**
	 * Kills all jobs
	 *
	 * @return \BZikarsky\Process\Queue
	 */
	public function stop()
	{
		foreach ($this->active as $pid => $job) {
			Controller::getInstance()->kill($pid);
		}
		
		return $this;
	}
	
	/**
	 * Checks if fork-limit has been hit
	 * 
	 * @return boolean
	 */
	public function isMaxed()
	{	
		return $this->limit > 0 && count($this->active) >= $this->limit;
	}
	
	/**
	 * Returns active jobs
	 * 
	 * @return array
	 */
	public function getActive()
	{
		return $this->active;
	}
	
	/**
	 * Checks if any jobs are active
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return count($this->active) > 0;
	}
	
	/**
	 * Returns the count of pending jobs
	 * 
	 * @return int
	 */
	public function count()
	{
		return count($this->jobs);
	}
	
	/**
	 * Returns the max fork limit
	 * 
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}
	
	/**
	 * Sets the max forks limit
	 * 
	 * @param int $limit
	 * @return \BZikarsky\Process\Queue
	 */
	public function setLimit($limit)
	{
		$this->limit = intval($limit);
		
		// react to limit changes
		if ($this->isActive()) {
			$this->execute();
		}
		
		return $this;
	}
	
	/**
	 * Returns the queue flags
	 * 
	 * @return int
	 */
	public function getFlags()
	{
		return $this->flags;
	}
	
	/**
	 * Sets the queue flags
	 * 
	 * @param int $flags
	 * @return \BZikarsky\Process\Queue
	 */
	public function setFlags($flags)
	{
		$this->flags = intval($flags);
		return $this;
	}
	
	/**
	 * Returns the queues own semaphore
	 * 
	 * @return resource
	 */
	public function getSemaphore()
	{
        return $this->semaphore;
	}
}