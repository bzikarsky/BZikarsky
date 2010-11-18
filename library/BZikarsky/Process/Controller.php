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
        if (!pcntl_signal(\SIGCHLD, array($this, 'childExited'), true)) {
            throw new Exception("pcntl_signal() failed");
        }
		
		// save parent PID
		$this->pid = posix_getpid();
    }
    
    /**
     * Destructor - ensure that there are no zombie-processes
     */
    public function __destruct()
    {
        if (posix_getpid() == $this->pid) {
            while (count($this->processes)) {
                $this->wait();
            }
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
        if ($pid == 0 || !isset($this->processes[$pid])) {
            return;
        }
        
        $this->processes[$pid]->exited($status);
        unset($this->processes[$pid]);
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
        // if there is nor process, no need to wait
        if (!count($this->processes)) {
            return;
        }
        
        $status = null;
        if (-1 == ($pid = pcntl_waitpid($pid, $status, WUNTRACED))) {
            // PHP 5.3.3 does not provide `perror` from waitpid in userland code
            // waitpid can either fail with ECHILD, meaning that there are no child 
            // processes, which can be ignored, since it's caught by our check.
            // Or it fails with EINTR, when a signal comes in, which is ok as well, 
            // we just drop out and handle the signal
            return;
        }
        
        // tell process of its end and unlock signal hadler
        $this->processExited($pid, $status);
    }
    
    /**
     * Renews the controller in a forked process
     * 
     * Since a forked process can fork itself new processes
     * a clean, new instance of the controller is required in 
     * every process.
     */
    public static function forked()
    {
        if (posix_getpid() == self::getInstance()->pid) {
            throw new Exception("Controller is not forked");
        }
        
        self::$instance = new self();
    }
    
}
