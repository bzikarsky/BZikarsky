<?php

namespace BZikarsky\Process;

// always enable ticks at parent file
declare(ticks=1);
require_once dirname(__FILE__) . '/../../autoload.php';



$work = function($job) {
    $sleep = rand(1, 5);
    sleep($sleep);
    return rand(0, rand(0, 1));
};


$jobs = array();
$queue = new Queue(3);
for ($i=0; $i<10; $i++) {
    $job = new Process($work);
        $jobs[] = $job;
    $queue->insert($job);       
}

$queue->start();


while (true)
{
    $finished = 0;
    foreach ($jobs as $job) {
        echo str_pad($job->getStatus(), 10), " ";
        if (in_array($job->getStatus(), 
                array(Process::STATUS_FINISHED, Process::STATUS_FAILURE, Process::STATUS_KILLED))) {
            $finished++;
        }
        
    }
    
    echo "\n";

    if ($jobs[5]->getStatus() == Process::STATUS_RUNNING) {
        $jobs[5]->kill();
    }
    
    if ($finished == count($jobs)) {
        break;
    }
    
    usleep(500000);
}
