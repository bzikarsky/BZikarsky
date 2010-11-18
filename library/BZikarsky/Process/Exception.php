<?php

namespace BZikarsky\Process;

/**
 * Base exception
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) 2010 Benjamin Zikarsky
 * @license   <http://opensource.org/licenses/bsd-license.php> New BSD
 */
class Exception extends \Exception
{
    public function __construct($message)
    {
        // always prepend the process id, when a Process Exception is thrown
        parent::__construct(sprintf("[pid:%d] %s", posix_getpid(), $message));
    }


}
