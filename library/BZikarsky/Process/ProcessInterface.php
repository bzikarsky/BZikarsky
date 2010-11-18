<?php

namespace BZikarsky\Process;

/**
 * ProcessInterface defines a child-process' structure
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) 2010 Benjamin Zikarsky
 * @license   <http://opensource.org/licenses/bsd-license.php> New BSD
 */
interface ProcessInterface
{
    /**
     * Exitcode if the process exited sucessfully
     */
	const EXIT_SUCCESS = 0;
	
	/**
	 * Exitcode if the process exited with an error
	 */
	const EXIT_FAILURE = 1;

    /**
     * Status when the process has not been run yet
     */
	const STATUS_PENDING  = 'pending';
	
	/**
	 * Status when the process is running
	 */
	const STATUS_RUNNING  = 'running';
	
	/**
	 * Status when the process finished successfully
	 */
	const STATUS_FINISHED = 'finished';
	
	/**
	 * Status when the process has been killed by a signal
	 */
	const STATUS_KILLED   = 'killed';
	
	/**
	 * Status when the process exited with a failure
	 */
	const STATUS_FAILURE  = 'failure';
	
	/**
	 * Event which is fired when the process is started
	 */
	const EVENT_START = 'start';
	
	/**
	 * Event which is fired when the process stopped
	 */
	const EVENT_EXIT = 'exit';

    /**
     * Returns the process pid
     *
     * @return integer
     */
	public function getPid();
	
	/**
	 * Returns the process' status
	 *
	 * @return integer
	 */
	public function getStatus();
	
	/**
	 * Returns the proces'' exit status
	 *
	 * @return integer
	 */
	public function getExitStatus();
	
	/**
	 * Runs the process
	 *
	 * @return \BZikarsky\Process\ProcessInterface
	 */
	public function run();
	
	/**
     * Waits for the current process to finish
     * Returns immediatly if the process is not running
     *
     * @return \BZikarsky\Process\ProcessInterface
     */
	public function join();
	
	/**
     * Callback which is called by the controller in the parent process
     * when the process is finished
     *
     * @param integer $status
     */
	public function exited($status);
	
	/**
     * Sends the given signal to the process
     *
     * @param integer $signal defaults to SIGHUP
     * @return \BZikarsky\Process\ProcessInterface
     */
	public function kill($signal=SIGHUP);
	
	/**
     * Attaches an event listener
     *
     * @param string $event
     * @param mixed  $callable
     * @returns \BZikarsky\Process\ProcessInterface
     */
	public function addEventListener($event, $callable);
	
}



