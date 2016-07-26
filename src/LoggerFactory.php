<?php namespace Common\Logger;

use Common\Dependency\Dependency;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerFactory extends Dependency
{
    const MONGO_CLIENT = 'mongo';
    const LOGGERS = 'logger';
    const NULL_LOGGER = 'null_logger';
    const CFG_HANDLERS = 'handlers';

    protected function __construct()
    {
        parent::__construct();
        self::import([
            self::LOGGERS => function () {
                return [Logger::_makeLogger()];
            },
            self::NULL_LOGGER => function () {
                return new NullLogger();
            }
        ]);
    }

    /**
     * @return LoggerInterface[]
     */
    public function loggers()
    {
        return $this->packageFetch(self::LOGGERS);
    }

    /**
     * @return LoggerInterface
     */
    public function nullLogger()
    {
        return $this->packageFetch(self::NULL_LOGGER);
    }


    /**
     * @return \MongoClient
     */
    public function mongo()
    {
        return $this->packageFetch(self::MONGO_CLIENT);
    }
}
