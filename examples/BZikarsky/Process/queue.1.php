<?php

namespace BZikarsky\Process;

// always enable ticks at parent file
declare(ticks=1);
require_once dirname(__FILE__) . '/../../autoload.php';



$work = function(ProcessInterface $job) {
    sleep(rand(1, 5));

    $result = rand(0, 3);
    
    // 25% probability "STATUS_KILLED"
    if ($result == 3) {
        $job->kill();
    }
    
    // 25% probability "STATUS_FAILURE"
    if ($result == 2) {
        return 1;
    }
    
    // 50% probability "STATUS_FINISHED"
    return 0;
};

// fill queue with jobs
$queue = new Queue(3);
for ($i=0; $i<10; $i++) {
    $queue->insert($work);       
}

// start queue
$queue->start();


// monitor job status
while ($queue->isActive())
{
    foreach ($queue->getFinished() as $job) {
        echo str_pad($job->getStatus(), 10), " ";
    }
    
    echo "\n";
    usleep(500000);
}
