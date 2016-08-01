<?php 
namespace Common\Logger;

use Monolog\Formatter\NormalizerFormatter;

use Exception;

/**
 * @author wangtao
 */
class FileLineFormatter extends NormalizerFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";

    protected $format;
    protected $allowInlineLineBreaks;
    protected $ignoreEmptyContextAndExtra;
    protected $includeStacktraces;

    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = false)
    {
        $this->format = $format ?: static::SIMPLE_FORMAT;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        parent::__construct($dateFormat);
    }

    public function includeStacktraces($include = true)
    {
        $this->includeStacktraces = $include;
        if ($this->includeStacktraces) {
            $this->allowInlineLineBreaks = true;
        }
    }

    public function allowInlineLineBreaks($allow = true)
    {
        $this->allowInlineLineBreaks = $allow;
    }

    public function ignoreEmptyContextAndExtra($ignore = true)
    {
        $this->ignoreEmptyContextAndExtra = $ignore;
    }

   
    public function format(array $record)
    {
        $vars = parent::format($record);

        $output = $this->format;

        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }

            if (empty($vars['extra'])) {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }

        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
            }
        }

        return $output;
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    public function stringify($value)
    {
        return $this->replaceNewlines($this->convertToString($value));
    }

    protected function normalizeException(Exception $e)
    {
        $previousText = '';
        if ($previous = $e->getPrevious()) {
            do {
                $previousText .= ', '.get_class($previous).'(code: '.$previous->getCode().'): '.$previous->getMessage().' at '.$previous->getFile().':'.$previous->getLine();
            } while ($previous = $previous->getPrevious());
        }

        $str = '[object] ('.get_class($e).'(code: '.$e->getCode().'): '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine().$previousText.')';
        if ($this->includeStacktraces) {
            $str .= "\n[stacktrace]\n".$e->getTraceAsString();
        }

        return $str;
    }

    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return $data;
            return (string) $data;
        }

        return str_replace('\\/', '/', @self::extract_message($data));
    }

    protected function replaceNewlines($str)
    {
        if ($this->allowInlineLineBreaks) {
            return $str;
        }

        return strtr($str, array("\r\n" => ' ', "\r" => ' ', "\n" => ' '));
    }
    

    protected static final function extract_message($input)
    {
        $output_str = '';
        switch (gettype($input)) {
            case 'array':
                $output_str = self::export($input, TRUE);
                break;
            case 'object':
                if (method_exists($input, 'getMessage')) {
                    $output_str = $input->getMessage();
                } else
                    if (method_exists($input, 'toString')) {
                        $output_str = $input->toString();
                    } else
                        if (method_exists($input, '__tostring')) {
                            $output_str = (string)$input;
                        } else {
                            $output_str = self::export($input, TRUE);
                        }
    
                        if (method_exists($input, 'getFile')) {
                            $file = $input->getFile();
                        }
                        if (method_exists($input, 'getLine')) {
                            $line = $input->getLine();
                        }
                        if (isset($file) && isset($line)) $output_str = sprintf("%s %s(%d)", $output_str, $file, $line);
    
                        break;
            default:
                $output_str = (string)$input;
                break;
        }
        return $output_str;
    }
    
  protected static function export($expression, $return = FALSE)
    {
        $dump = self::dump($expression);
        if ($return) {
            return $dump;
        }
        print $dump;
    }
   protected  static function dump($var, $max_depth = 5, $depth = 0)
    {
        if (is_object($var) || is_array($var)) {
            if (is_object($var)) {
                $class_name = get_class($var);
                $var = (array) $var;
            } else {
                $class_name = "array";
            }
            $s = "$class_name(\n";
            if (++ $depth > $max_depth) {
                $s .= str_repeat('    ', $depth) . "?";
            } else {
                $flatten = array();
                foreach ($var as $k => $v) {
                    $flatten[] = str_repeat('    ', $depth) . "$k: " . self::dump($v, $max_depth, $depth);
                }
                $s .= implode(",\n", $flatten);
            }
            $s .= "\n" . str_repeat('    ', $depth - 1) . ")";
            return $s;
        }
        return "$var";
    }
    
}
