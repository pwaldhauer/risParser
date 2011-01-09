<?php

/* * ****************************************************************************
 *
 * risParser - parse delay information of DB
 * Copyright (C) 2011 Philipp Waldhauer
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA 02110, USA
 *
 * *************************************************************************** */

class TimeEntry {

    public $time;
    public $delay;

    /**
     * Gets the delay status.
     * @return bool true if the time is delayed, false if not
     */
    public function isDelayed() {
        return $this->delay != 0;
    }

    /**
     * Gets the original time plus the delay.
     * @return int seconds since unix time
     */
    public function getRealTime() {
        return $this->time + $this->delay;
    }

    public function toString() {
        if ($this->time == 0) {
            return 'No time given';
        }

        return date('H:i:s', $this->getRealTime());
    }

}

class StationEntry {

    public $name;
    public $inTime;
    public $outTime;

}

class TrainEntry {

    public $type;
    public $name;
    public $stations = array();

}

class TrainType {
    const ICE = 1;
    const IC = 2;
    const IR = 4;
    const RE = 8;

    public static function getByName($str) {
        switch ($str) {
            case 'IC':
                return self::IC;
            case 'ICE':
                return self::ICE;
            case 'TGV':
                return self::ICE;
            case 'IR':
                return self::IR;
            case 'RE':
                return self::RE;
            case 'RB':
                return self::RB;
            default:
                return 0;
        }
    }

}

class RisReader {

    /**
     * Gets the available information for the given train. Returns null
     * if the train is not found.
     * @param int $type Type of the train, see TrainType
     * @param type $name Name/Number of the train
     * @return TrainEntry 
     */
    public function getTrain($type, $name) {
        $req = new RisRequest($type, $name);
        $content = str_replace(array("\r", "\n"), '', $req->request());

        if (!preg_match('#<div class="haupt bold">(.*?)</div>#is', $content, $match)) {
            return null;
        }

        $train = new TrainEntry();
        $train->type = $type;
        $train->name = $match[1];

        preg_match_all('#<tr><td class="arrival tqdetail">(.*?)</td><td class="tqdetail rt top">(.*?)</td><td class="station tqdetail top">(.*?)</td></tr>#is', $content, $match);

        $count = count($match[0]);

        for ($i = 0; $i < $count; $i++) {
            $station = new StationEntry();
            $station->name = $this->trimStationName($match[3][$i]);

            $station->inTime = new TimeEntry();
            $station->inTime->time = $this->trimTime(0, $match[1][$i]);
            $station->inTime->delay = $this->trimDelay(0, $match[2][$i]);

            $station->outTime = new TimeEntry();
            $station->outTime->time = $this->trimTime(1, $match[1][$i]);
            $station->outTime->delay = $this->trimDelay(1, $match[2][$i]);

            $train->stations[] = $station;
        }

        return $train;
    }

    private function trimTime($i, $time) {
        $tmp = explode('<br />', trim($time));

        if ($i >= count($tmp)) {
            return 0;
        }

        $str = $tmp[$i];

        if ($str == '&nbsp;') {
            return 0;
        }

        return strtotime(date('d.m.Y') . ' ' . $str . ':00');
    }

    private function trimDelay($i, $delay) {
        $tmp = explode('<br />', trim($delay));

        if ($i >= count($tmp)) {
            return 0;
        }

        if (preg_match('#<span class="bold red">\(\+([0-9]+)\)</span>#isU', $tmp[$i], $match)) {
            return intval($match[1]) * 60;
        }

        return 0;
    }

    private function trimStationName($str) {
        return trim(str_replace('<br />', '', html_entity_decode($str)));
    }

}

class RisRequest {

    private $date;
    private $productClassFilter;
    private $trainname;

    public function __construct($type, $name, $date = null) {
        $this->productClassFilter = $type;
        $this->trainname = $name;

        if ($date == null) {
            $date = date('d.m.y', time());
        }

        $this->date = $date;
    }

    public function request() {
        $data = 'start=Suchen&date=' . $this->date . '&productClassFilter=' . $this->productClassFilter . '&trainname=' . $this->trainname;
        $result = $this->postRequest('mobile.bahn.de', '/bin/mobil/trainsearch.exe/dox?ld=96236&amp;rt=1&amp;use_realtime_filter=1&amp;', 'http://m.bahn.de/', $data);

        return $result;
    }

    private function postRequest($host, $path, $referer, $data) {
        $fp = fsockopen($host, 80);

        fputs($fp, "POST $path HTTP/1.1\r\n");
        fputs($fp, "Host: $host\r\n");
        fputs($fp, "Referer: $referer\r\n");
        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: " . strlen($data) . "\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);

        while ($fp != false && !feof($fp)) {
            $res .= fgets($fp, 128);
        }

        fclose($fp);

        return $res;
    }

}

