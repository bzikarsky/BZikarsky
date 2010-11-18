<?php

namespace BZikarsky\SharedMemory;

/**
 * Semaphore is an OO wrapper for the sem_* functions of PHP
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) 2010 Benjamin Zikarsky
 * @license   <http://opensource.org/licenses/bsd-license.php> New BSD
 */
class Semaphore
{
    /**
     * The semaphore's id
     * 
     * @var int
     */
    protected $id = null;
    
    /**
     * The handle to the shared memory segment
     * 
     * @var resource
     */
    protected $sem = null;
    
    /**
     * The process' id in which the semaphore was created
     * 
     * @var $pid int
     */
    protected $pid = null;
    
    /**
     * Auto-remove the semaphore as soon the script ends
     * 
     * @var int
     */
    protected $autoRemove = null;
    
    /**
     * Maximum of semaphore acquirations
     * 
     * @var integer
     */
    protected $maxAcquire = null;
    
    /**
     * The access mode of the semaphore
     * 
     * @var integer
     */
    protected $mode = null;
    
    /**
     * Class constructor
     * 
     * @param String $name unique name of the semaphore
     * @param int $maxAcquire
     * @param int $mode
     * @param int $autoRemove
     */
    public function __construct($name='', $maxAcquire=1, $mode=0644, $autoRemove=1)
    {
        $this->id = self::createId($name, getmyinode());
        $this->maxAcquire = $maxAcquire;
        $this->mode = $mode;
        $this->autoRemove = $autoRemove;
        $this->pid = posix_getpid();
    }
    
    /**
     * Destrcutor
     * 
     * If the semaphore has been created with autoremove delete
     * the semaphore as soon the script ends
     */
    public function __destruct()
    {
        if ($this->sem 
            && $this->pid == posix_getpid()
            && $this->autoRemove == 1
        ) {
            $this->remove();
        }
    }
    
    /**
     * We do not want cloned object of the semaphore
     */
    private function __clone()
    {}
    
    /**
     * Generates a sempahore id from a name and an optional
     * file-inode. If no file-inode is given, the inode of
     * the excuted script is used
     * 
     * @param String $name
     * @param int $inode
     * @return int
     */
    public static function createId($name, $inode=null)
    {
        // build id from $name and $inode
        $inode = ($inode ?: getmyinode());
        $name  = hexdec(substr(md5($name), 24));
        
        return $inode + $name;
    }
    
    /**
     * Creates the semaphore
     */
    protected function create()
    {
        $this->sem = sem_get(
            $this->id, 
            $this->maxAcquire, 
            $this->mode,
            $this->autoRemove 
        );
        
        if (!$this->sem) {
            throw new Exception(sprintf("semaphore %d: sem_get() failed", $this->id));
        }
    }
    
    /**
     * Checks if the semaphore is already created
     * and if negative, creates it
     */
    protected function assertSemCreated()
    {
        if (!$this->sem) {
            $this->create();
        }
    }
    
    /**
     * Acquires a lock ion the semaphore
     */
    public function acquire()
    {
        $this->assertSemCreated();
        
        if (!sem_acquire($this->sem)) {
            throw new Exception(sprintf("semaphore %d: sem_acquire() failed", $this->id));
        }
    }
    
    /**
     * Releases a lock off the semaphore
     */
    public function release()
    {
        $this->assertSemCreated();
        
        if (!sem_release($this->sem)) {
            throw new Exception(sprintf("semaphore %d: sem_release() failed", $this->id));
        }
    }
    
    /**
     * Acquires and releases a semaphore
     */
    public function touch()
    {
        $this->acquire();
        $this->release();
    }
    
    /**
     * Removes a semaphore, regardless of its state
     */
    public function remove()
    {
        if (!sem_remove($this->sem)) {
            throw new Exception(sprintf("semaphore %d: sem_remove() failed", $this->id));
        }
        
        $this->sem = null;
    }

}