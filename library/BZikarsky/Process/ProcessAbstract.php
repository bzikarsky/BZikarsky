<?php

namespace BZikarsky\Process;

use BZikarsky\SharedMemory\Semaphore;

/**
 * ProcessAbstract provides methods for the creation and management of child 
 * processes
 *
 * ProcessAbstract is the defualt implementation of the logic required to 
 * handle child-processes effectively. It ties in with the Controller class
 * and should be used in most cases.
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) 2010 Benjamin Zikarsky
 * @license   <http://opensource.org/licenses/bsd-license.php> New BSD
 */
abstract class ProcessAbstract implements ProcessInterface
{	
	/**
	 * Status of the process
	 *
	 * @var string
	 */
    protected $status = self::STATUS_PENDING;
    
    /**
     * Pid of the process
     * 
     * @var integer
     */
    protected $pid = -1;
    
    /**
     * Exit status of the process
     *
     * @var integer
     */
    protected $exitStatus = 0;
    
    /**
     * Event listeners
     *
     * @var array
     */
    protected $listeners = array();
    
    /**
     * Process' semaphore
     * 
     * @var \BZikarsky\Process\Semaphore
     */
    protected $semaphore = null;
    
    /**
     * Runs the process
     *
     * The process is forked, and registered with the controller. 
     * The method calls execute as soon it is forked
     * To avoid race-conditions all processes use a semaphore to 
     * make sure parent is set up before the child can exit
     * If additional setup work has to be done by the caller before
     * before the execution starts (and possibly ends), 
     * $unlockSemaphore can be used to keep the locks
     *
     *
     * @return \BZikarsky\Process\ProcessInterface
     */
    public function run($unlockSempahore=true)
    {
        $controller  = Controller::getInstance();
        
        // lock & fork
        $this->getSemaphore()->acquire();
        $pid = pcntl_fork();
        
        // it's an error
        if ($pid == -1) {
            throw new Exception("pcntl_fork() failed");
        }
        
        // it's the child process
        if ($pid == 0) {
            $this->pid    = posix_getpid();
            $this->status = self::STATUS_RUNNING;
            
            // tell the controller we forked
            Controller::forked();
            
            // wait for the parent process to finish its startup 
            // procedure
            $this->getSemaphore()->touch();
            
            exit($this->execute());
        }
        
        // it's the parent process
        $this->pid    = $pid;
        $this->status = self::STATUS_RUNNING;
        
        // register with controller
        $controller->register($this);
        
        // if the calling process has to do
        // startup work itself, it can request
        // that the lock stays closed
        // The calling process then has to release
        // the sempahore itself
        if ($unlockSempahore) {
            $this->getSemaphore()->release();
        }
        
        $this->fire(self::EVENT_START);

        return $this;
    }
    
    /**
     * Waits for the current process to finish
     * Returns immediatly if the process is not running
     *
     * @return \BZikarsky\Process\ProcessInterface
     */
    public function join()
    {
		if ($this->getStatus() == self::STATUS_RUNNING) {
			Controller::getInstance()->wait($this->pid);
		}
		
		return $this;
    }
    
    /**
     * Sends the given signal to the process
     *
     * @param integer $signal defaults to SIGHUP
     * @return \BZikarsky\Process\ProcessInterface
     */
    public function kill($signal=SIGHUP)
    {
    	if ($this->getStatus() != self::STATUS_RUNNING) {
    		throw new Exception("Process is not running");
    	}
        
    	if (!posix_kill($this->getPid(), $signal)) {
    		throw new Exception("posix_kill() failed");
    	}
    	
    	return $this;
    }
    
    /**
     * Callback which is called by the controller in the parent process
     * when the process is finished
     *
     * @param integer $status
     */
    public function exited($status)
    {
        if ($this->getStatus() != self::STATUS_RUNNING) {
            throw new Exception("Process has not been started");
        }
    	
    	if (posix_kill($this->getPid(), 0)) {
    		throw new Exception("Process is still running");
    	}
        
        // save exitStatus
        $this->exitStatus = pcntl_wexitstatus($status);
        
        if (pcntl_wifsignaled($status)) {
            $this->status = self::STATUS_KILLED;
        } elseif ($this->exitStatus != self::EXIT_SUCCESS) {
            $this->status = self::STATUS_FAILURE;
        } else {
            $this->status = self::STATUS_FINISHED;
        }
        
        $this->getSemaphore()->remove();
        $this->fire(self::EVENT_EXIT);
    }
    
    /**
     * Returns the process' status
     * 
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Returns the process' pid
     *
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }
    
    /**
     * Returns the process' exit status
     *
     * @return integer
     */
    public function getExitStatus()
    {
        return $this->exitStatus;
    }
    
    /**
     * Attaches an event listener
     *
     * @param string $event
     * @param mixed  $callable
     * @returns \BZikarsky\Process\ProcessInterface
     */
    public function addEventListener($event, $callable)
    {
    	if (!is_callable($callable)) {
    		throw new \InvalidArgumentException("Callable must be a valid callback");
    	}
    	
    	if (!array_key_exists($event, $this->listeners)) {
    		$this->listeners[$event] = array();
    	}
    	
    	$this->listeners[$event][] = $callable;
    	return $this;
    }
    
    /**
     * Fires the event listeners connected to given event
     *
     * @param string $event
     */
    protected function fire($event)
    {
        if (!array_key_exists($event, $this->listeners)) {
            return;
        }	
        
        foreach ($this->listeners[$event] as $callback) {
        	call_user_func($callback, $this, $event);
        }
    }
    
    public function getSemaphore()
    {
        if (!$this->semaphore) {
            $this->semaphore = new Semaphore(
                spl_object_hash($this), 1, 0666, 0
            );
        }
        
        return $this->semaphore;
    }
    
    /**
     * Executes the process' logic
     *
     * @return int $exitCode
     */
    abstract protected function execute();
   
}
