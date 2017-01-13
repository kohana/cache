<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Kohana_Config_Cache_Reader
 *
 * wraps another Kohana_Config_Reader and provides cacheing for its results.
 *
 * @uses Kohana_Config_Reader
 * @author David Chan <dchan@sigilsoftware.com>
 * @license    http://kohanaframework.org/license
 */
class Kohana_Config_Cache_Reader implements Kohana_Config_Reader {

    /**
     * reader 
     *
     * @var Kohana_Config_Reader
     * @access protected
     */
    protected $reader = null;

    /**
     * Constructor
     *
     * @param Kohana_Config_Reader $reader
     * @access public
     */
    public function __construct(Kohana_Config_Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * load
     *
     * @param mixed $group
     * @access public
     * @return array
     */
    public function load($group)
    {
        $key = sprintf('%s[%s]', get_class($this->reader), $group);

        $cached = Cache::instance()->get($key, false);
        if ($cached === false)
        {            
            $fresh = $this->reader->load($group);
            if ($fresh !== false)
            {
                Cache::instance()->set($key, $this->encode($fresh));
                return $fresh;
            }
        }
        return $this->decode($key, $cached);
    }

    /**
     * encode
     *
     * over ride me if you want to change the encoding or error handling
     *
     * @param mixed $data
     * @access protected
     * @return string
     */
    protected function encode($data)
    {
        return json_encode($data,JSON_UNESCAPED_UNICODE);
    }

    /**
     * decode
     *
     * over ride me if you want to change the decoding or error handling
     *
     * @param string $key
     * @param string $code
     * @access protected
     * @return mixed
     */
    protected function decode($key, $code)
    {
        $data = json_decode($code);
        if (JSON_ERROR_NONE != json_last_error())
        {
            throw new Kohana_Exception(sprintf('Cached Key, %s, got error, %s', $key, json_last_error_msg()));
        }
        return $data;       
    }
}
