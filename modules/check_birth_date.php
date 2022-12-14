<?php
    function checkDateTime($date)
    {
        $date = str_replace('T', ' ', $date);
        $date = mb_substr($date, 0, strlen($date)-5);
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        if ($d === false) {
            return false;
        } else {
            return $d->getTimestamp();
        }
    }