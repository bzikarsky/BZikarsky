<?php

namespace BZikarsky\Process;

/**
 * Controller class manages the children processes and their termination
 *
 * The controller is a singleton, which knows about every new subprocess. It 
 * is responsible for handling the SIGCHLD signal, and notifying the proxy
 * process objects in the master-proccess accordingly
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) 2010 Benjamin Zikarsky
 * @license   <http://opensource.org/licenses/bsd-license.php> New BSD
 */
final class Controller
{
	/**
	 * Singleton instance
	 * 
	 * @var \BZikarsky\Process\Controller
	 */
    protected static $instance = null;
    
    /**
     * List of running processes
     *
     * @var array
     */
    protected $processes = array();
    
    /**
     * Flag is true, when wait has been called and signal-handling has
     * to be stopped
     * 
     * @var boolean
     */
    protected $waiting = false;
    
    /**
     * The controllers semaphore for locking $processes
     *
     * @var resource
     */
    protected $semaphore = null;

	/**
	 * PID of the parent process
	 *
	 * @var integer
	 */
	protected $pid = null;

	/**
	 * Constructor
	 *
	 * Since the class is a singleton, the constructor is private
	 */
    private function __construct() 
    {
    	// setup signal handler
        if (!pcntl_signal(\SIGCHLD, array($this, 'childExited'), false)) {
            throw new Exception("pcntl_signal() failed");
        }
        
        // setup sempahore
        if (!($this->semaphore = sem_get(ftok(__FILE__, 'r'), 1, 0600, 1))) {
			throw new Exception("sem_get failed()");
		}
		
		// save parent PID
		$this->pid = posix_getpid();
    }
    
    /**
     * Destructor - ensure that there are no zombie-processes
     */
    public function __destruct()
    {
		// only wait in parent-process
		if ($this->pid  != posix_getpid()) {
			return;
		}
    }
        
    /**
     * Clone interceptor is private since the Controller is a Singleton
     */    
    private function __clone() {}
    
    /**
     * Returns the controller instance
     *
     * @return \BZikarsky\Process\Controller
     */
    public function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Signalhandler for SIGCHLD
     */
    public function childExited()
    {
		// if the controller is in wait-mode, we ignore SIGCHLD signals
        if ($this->waiting) {
            return;
        }
        
        /**
         * SIGCHLD signals are handled in wait()
         */
        $this->wait();
    }
    
    /**
     * Internal callback, when a process has quit 
     *
     * @param int $pid    of the process
     * @param int $status of the process
     */
    protected function processExited($pid, $status)
    {
        // ignore unknown or "invalid" (pid == 0) processes
        if ($pid == 0 || !array_key_exists($pid, $this->processes)) {
            return;
        }
        
        sem_acquire($this->getSemaphore());			// lock
        $this->processes[$pid]->exited($status);	// tell the proxy-object of its end
        unset($this->processes[$pid]);				// remove from running processes
        sem_release($this->getSemaphore());			// unlock
    }
    
    /**
     * Registers a new process with the Controller
     *
     * @param \BZikarsky\Process\ProcessInterface $process
     */
    public function register(ProcessInterface $process)
    {       
        // check that the process exists and is signalable 
        if (!posix_kill($process->getPid(), 0)) {
            throw new Exception("Process is not signalable");
        }
        
        $this->processes[$process->getPid()] = $process;
    }
    
    /**
     * Waits for a process to finish
     * 
     * @param integer $pid of the process to be waited for, or 0 (default) for
	 *                     any child
	 */
    public function wait($pid=0)
    {
    	// block signal handler
        $this->waiting = true;

        $status = null;
        if (-1 == ($pid = pcntl_waitpid($pid, $status))) {
            throw new Exception("pcntl_waitpid() failed");
        }
        
        // tell process of its end and unlock signal hadler
        $this->processExited($pid, $status);
        $this->waiting = false;
    }
    
    /**
     * Returns the semaphore
     *
     * @return resource*
     */
    public function getSemaphore()
    {
    	return $this->semaphore;
    }
}
