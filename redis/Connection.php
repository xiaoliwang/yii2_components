<?php
/**
 * Created by PhpStorm.
 * User: TomCao
 * Date: 2017/5/6
 * Time: 下午11:12
 */

namespace app\components\redis;

use phpDocumentor\Reflection\Types\Null_;
use Yii;
use Redis;
use yii\base\Configurable;
use RedisException;
use yii\base\InvalidParamException;

class Connection extends Redis implements Configurable
{
    public $hostname = 'localhost';
    public $port = 6379;
    public $unixSocket;
    public $password;
    public $database = 0;
    public $connectionTimeout = 0.0;

    public static function className()
    {
        return get_called_class();
    }

    public function __construct(array $config = [])
    {
        if ($config) {
            Yii::configure($this, $config);
        }
        $this->init();
    }

    public function init()
    {
        $this->_connect();
    }

    private function _connect()
    {
        if ($this->unixSocket) {
            $isConnected = $this->connect($this->unixSocket);
        } else {
            $isConnected = $this->pconnect($this->hostname, $this->port, $this->connectionTimeout);
        }

        if ($isConnected === false) throw new RedisException('Connection refused');

        if ($this->password) {
            $this->auth($this->password);
        }

        if ($this->database !== null) {
            $this->select($this->database);
        }

        if ($this->ping() !== '+PONG') throw new RedisException('NOAUTH Authentication Required.');
    }

    public function generateKey($pattern, ...$args): string
    {
        $str_count = substr_count($pattern, '*');
        $arg_count = count($args);
        if ($str_count !== $arg_count)
            throw new InvalidParamException("pattern expects $str_count arguments $arg_count given");
        $pattern = str_replace('*', '%s', $pattern);
        $key = sprintf($pattern, ...$args);
        return $key;
    }

    public function delPatternKeys($pattern)
    {
        $cursor = Null;
        do {
            $keys = $this->scan($cursor, $pattern, 1000);
            $this->del($keys);
        } while($cursor);
        return true;
    }
}