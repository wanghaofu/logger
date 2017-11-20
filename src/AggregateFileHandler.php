<?php
namespace Common\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class AggregateFileHandler extends StreamHandler
{
    public function handleBatch( array $records )
    {
        $dur = number_format( microtime( true ) - LARAVEL_START, 3 );
        $request = request();
        $format = 'Y-m-d H:i:s.u';
// 这一行是我们这个处理器自己加上的日志，记录请求时间、响应时间、访客IP，请求方法、请求Url
        $log = sprintf(
            "[%s][%s]%s %s %s\n",
            Carbon::createFromFormat( 'U.u', sprintf( '%.6F', LARAVEL_START ), config( 'app.timezone' ) )->format( $format ),
            $dur,
            $request->getClientIp(),
            $request->getMethod(),
            $request->getRequestUri()
        );
// 然后将内存中的日志追加到$log这个变量里
        foreach ( $records as $record ) {
            if ( !$this->isHandling( $record ) ) {
                continue;
            }
            $record = $this->processRecord( $record );
            $log .= $this->getFormatter()->format( $record );
        }
// 调用日志写入方法
        $this->write( [ 'formatted' => $log ] );
    }
}

/**
$handler = new AggregateFileHandler($path_to_log_file);
$handler->setFormatter(
    new LineFormatter("[%datetime%]%level_name% %message% %context% %extra%\n", 'i:s', true, true)
);
$monolog->pushHandler(new BufferHandler($handler));
 *
 *
input {
file {
type => 'monolog'
path => "/home/vagrant/website/logs/app.log"
}
}
filter {
if [type] == 'monolog' {
multiline {
pattern => "\[[\d\-\: ]+?\]\[[\d\.]+?\]\d+\.\d+\.\d+\.\d+ \S+ \S+"
negate => true
what => "previous"
}
grok {
match => ["message", "\[%{TIMESTAMP_ISO8601:time}\]\[%{NUMBER:duration}\]%{IP:ip} %{WORD:method} %{DATA:url}\n%{GREEDYDATA:data}"]
}
}
}
 *
 * **/