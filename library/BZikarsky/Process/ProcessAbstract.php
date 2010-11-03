<?php

namespace BZikarsky\Process;

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
     * Runs the process
     *
     * The process is forked, and registered with the controller. 
     * For stability the controller's semaphore is used.
     * The method calls execute as soon it is forked
     *
     * @return \BZikarsky\Process\ProcessInterface
     */
    public function run()
    {
    	$controller = Controller::getInstance();
    	sem_acquire($controller->getSemaphore());
    	
        // fork
        $pid = pcntl_fork();
        
        // it's an error
        if ($pid == -1) {
            throw new Exception("pcntl_fork() failed");
        }
        
        // it's the child process
        if ($pid == 0) {
            $this->pid    = posix_getpid();
            $this->status = self::STATUS_RUNNING;
            exit($this->execute());
        }
        
        // it's the parent process
        $this->pid    = $pid;
        $this->status = self::STATUS_RUNNING;
        
        // register with controller
        $controller->register($this);
        sem_release($controller->getSemaphore());
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
    
    /**
     * Executes the process' logic
     *
     * @return int $exitCode
     */
    abstract protected function execute();
}
