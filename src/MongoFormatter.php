<?php namespace Common\Logger;

use Monolog\Formatter\NormalizerFormatter;

class MongoFormatter extends NormalizerFormatter
{
    protected function normalize($data)
    {
        if ($data instanceof \DateTime) {
            return new \MongoDate($data->getTimestamp());
        }

        return parent::normalize($data);
    }
}
 
