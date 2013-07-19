<?php
class Iron_Time
{
    protected $_time;

    public function __construct($time = null)
    {

        if (is_null($time)) {
            $this->_time = time();
            return;
        }

        // Número de segundos
        if (is_numeric($time)) {
            $this->_time = $time;
            return;
        }

        //hh:mm:[ss]
        if (is_string($time)) {
            $this->_time = $this->_parseTimeStr($time);
            return;
        }

        $this->_time = 0;
    }

    protected function _parseTimeStr($time)
    {

        $time = preg_replace("/[^0-9:]+/", '', $time);


        $segments = explode(":", $time);

        $segmentsItems = array("hora","min","seg");
        $items = array();

        foreach ($segments as $idx => $segment) {
            $items[$segmentsItems[$idx]] = $segment;
        }

        if (!isset($items['min']) && !isset($items['seg'])) {

            if (!isset($items['hora'])) return 0;

            return (int)$items['hora'];
        }

        if ( !isset($items['seg'])) {

            return ( $items['hora']*3600) + ( $items['min']*60);
        }

        return ( $items['hora']*3600) + ( $items['min']*60) + $items['seg'];

    }


    public function getFormattedString($format = null)
    {
        if (is_null($format)) {
            return self::secondsToTime($this->_time);
        }

        $format = strtolower($format);

        $replaces = array(
                'hh'=> sprintf('%02d', self::_getHours($this->_time)), //Hour, (00-12), two digit pr more
                'h' => sprintf('%d', self::_getHours($this->_time)), //Hour, (0-10000), one or two digit
                'mm'=> sprintf('%02d', self::_getMinutes($this->_time)), //Minutes, (00-12), two digit pr more
                'm' => sprintf('%d', self::_getMinutes($this->_time)), //Minutes, (0-10000), one or two digit
                'ss'=> sprintf('%02d', self::_getSeconds($this->_time)), //Seconds, (0-10000), one or two digit
                's' => sprintf('%d', self::_getSeconds($this->_time)) //Seconds, (0-10000), one or two digit
        ); //Seconds, (00-12), two digit pr more

        return str_replace(array_keys($replaces), $replaces, $format);
    }

    protected static function _getHours($time)
    {
        return floor($time / 3600);
    }

    protected static function _getMinutes($time)
    {
        $remainingTime = $time % 3600;
        return floor($remainingTime / 60);

    }

    protected static function _getSeconds($time)
    {
        $remainingTime = $time % 3600;
        return $remainingTime % 60;
    }

    public static function secondsToTime($time)
    {
        $hours = self::_getHours($time);
        $minutes = self::_getMinutes($time);
        $seconds = self::_getSeconds($time);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

    }
}
