<?php namespace Common\Logger;

use Common\Dependency\Traits\SingletonTrait;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\MongoDBHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Formatter\LineFormatter;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    use SingletonTrait;

    public static function _makeLogger()
    {
        $factory = LoggerFactory::instance();

        $logger = new MonologLogger('main');

//        this is for  //packageConfig get the key value from config key is handlers
        foreach ($factory->packageConfig(LoggerFactory::CFG_HANDLERS) as $handler) {
            switch ($handler['type']) {
                case 'syslog':
                    $syslog = new SyslogHandler($handler['ident'],$handler['facility'], $handler['level']);
                    $formatter = new LineFormatter("%channel%.%level_name%: %message% %extra%");
                    $syslog->setFormatter($formatter);
                    $logger->pushHandler($syslog);
                    break;
                case 'mongo':
                    $mongoHandler = new MongoDBHandler(
                        $factory->mongo(),
                        $handler['db'],
                        $handler['collection'],
                        $handler['level']);
                    $mongoHandler->setFormatter(new MongoFormatter());
                    $logger->pushHandler($mongoHandler);

                    break;
                case 'rotating_file':
                    $dir = dirname($handler['path']);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0777, true);
                    }
                    // new format
                    //file
                    $rotatingFile = new RotatingFileHandler(
                        $handler['path'],
                        $handler['limit'],
                        $handler['level'],
                        true,
                        null,
                        true//use locking = true : 使用flock阻止并发写
                    );

                    //format set
                    if(0) {
                        $dateFormat = "Y-m-d H:m:i";
                        $output = "[%datetime%] %level_name% : %message% %context% \n";
                        $formatter = new FileLineFormatter( null, null, true, true );
                        $rotatingFile->setFormatter( $formatter );
                    }

                    $logger->pushHandler( $rotatingFile );
                    // old format
//                    $logger->pushHandler(
//                        new RotatingFileHandler($handler['path'], $handler['limit'], $handler['level'])
//                    );
                    break;
                default:
                    throw new \Exception('config error');
            }
        }

        return $logger;
    }

    protected static function formatTrace($trace)
    {
        $result = array();
        $traceline = '#%s %4$s(%5$s) @ %2$s:%3$s';
        $key = 0;
        array_shift($trace);
        foreach ($trace as $key => $stackPoint) {

            if (isset($stackPoint['args'])) {
                foreach ($stackPoint['args'] as $k => $arg) {
                    unset($stackPoint['args'][$k]); //args下可能有引用，先unset防止串改
                    $stackPoint['args'][$k] = is_scalar($arg) ? var_export($arg, true) : (is_object($arg) ? get_class(
                        $arg
                    ) : gettype($arg));
                }
            } else {
                $stackPoint['args'] = array();
            }
            unset($arg);
            $fn = isset($stackPoint['class'])
                ? "{$stackPoint['class']}{$stackPoint['type']}{$stackPoint['function']}"
                : $stackPoint['function'];

            $result[] = sprintf(
                $traceline,
                $key,
                @$stackPoint['file'],
                @$stackPoint['line'],
                $fn,
                implode(', ', $stackPoint['args'])
            );
        }

        $result[] = '#' . ++$key . ' {main}';
        return $result;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $context['_trace'] = self::formatTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
            /**
             * @var \Exception $exception
             */
            $exception = $context['exception'];
            $context['_exception_trace'] = self::formatTrace($exception->getTrace());
        }
        foreach (LoggerFactory::instance()->loggers() as $logger) {
            try{
                $logger->log($level, $message, $context);
            }catch (\Exception $e){
                error_log($e->getMessage());
            }
        }
    }
}
