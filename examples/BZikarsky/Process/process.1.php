<?php

namespace BZikarsky\Process;

// always enable ticks at parent file
declare(ticks=1);
require_once dirname(__FILE__) . '/../../autoload.php';

$work = function(Process $p) {
    $sleep = rand(2, 5);
    echo sprintf("[pid-%d] starting\n", $p->getPid());
    echo sprintf("[pid-%d] sleeping %ds\n", $p->getPid(), $sleep);
    sleep($sleep);
    echo sprintf("[pid-%d] stopping\n", $p->getPid());
};

for ($i=0; $i<10; $i++) {
    $p = new Process($work);
    $p->run();
}
