<?php

namespace Micx\FormMailer\Stats;

class Visitor
{

    public $total_online_time = 0;
    public $total_pause_time = 0;

    public function __construct(
        public string $ts,
        public string $visitor_id,
        public string $ip,
        public string $host,
        public string $visitor_id_gmdate,

        public string $last_visit_gmdate,
        public int $visits,
        public string $language,
        public string $user_agent,
        public string $screen,


    ){}

    public $track = [];

    public function inject(array $data)
    {
        static $last = null;
        $this->total_online_time += $data["duration"];

        if ($last !== null && $last["session_seq"] === $data["session_seq"]) {
            $last["duration"] += $data["duration"];
            $last["wakeups"] = $data["wakeups"];
            return;
        }

        $last =& $data;
        $this->track[] =& $data;
    }

    public function getReport() : string
    {
        $ret  = "\n-----------------------------";
        $ret .= "\nHit-Date..: " . date("Y-m-d H:i:s", $this->ts);
        $ret .= "\nHost: " . $this->host . " Duration: " . $this->total_online_time;
        $ret .= "\nUser-Agent: " . $this->user_agent;
        $ret .= "\nScreen: " . $this->screen . " Lang: " . $this->language;
        $ret .= "\nFirst-Visit: " . $this->visitor_id_gmdate . " Last-Visit: " . $this->last_visit_gmdate . " Visits: " . $this->visits;
        $ret .= "\n";
        foreach ($this->track as $track) {
            $ret .= "\n" . $track["href"] . " [" . (int)$track["duration"] . "]";
        }
        return $ret;
    }


}
