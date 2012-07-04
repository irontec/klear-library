<?php
class  Iron_Time 
{
    protected $_time;
    
    public function __construct($time = null)
    {
        if (is_null($time) {
            $this->_time = time();
        } else {
            $this->_time = $time;
        }
    }

    public function getFormattedString()
    {
        return self::secondsToTime($this->_time);
    }

    public static function secondsToTime($time)
    {
        $hours = floor($time / 3600);
        $remainingTime = $time % 3600;
        $minutes = floor($remainingTime / 60);
        $seconds = $remainingTime % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

    }
}
