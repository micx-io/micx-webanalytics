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
        public string $session_id,
        public string $href


    ){}

    public $track = [];

    public $conversions = [];

    public function inject(array $data)
    {
        static $last = null;
        $this->total_online_time += $data["duration"];

        foreach ($data["conversions"] ?? [] as $key => $value) {
            if ( ! isset ($this->conversions[$key]))
                $this->conversions[$key] = 0;
            $this->conversions[$key]++;
        }

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
        $link = explode("?", explode("#", $this->href)[0])[0] . "?micx-wa-disable=1&micx-wa-session={$this->session_id}&micx-wa-key=" . sha1(FE_SECRET . $this->session_id);

        $ret  = "\n-----------------------------";
        $ret  .= "\nReplay: $link";

        $ret .= "\nHit-Date..: " . date("Y-m-d H:i:s", $this->ts);
        $ret .= "\nHost: " . $this->host . " Duration: " . $this->total_online_time;
        $ret .= "\nUser-Agent: " . $this->user_agent;
        $ret .= "\nScreen: " . $this->screen . " Lang: " . $this->language;
        $ret .= "\nFirst-Visit: " . $this->visitor_id_gmdate . " Last-Visit: " . $this->last_visit_gmdate . " Visits: " . $this->visits;
        $ret .= "\nConversions: " . implode(",", array_keys($this->conversions ?? []));
        $ret .= "\n";
        foreach ($this->track as $track) {
            $track["mouse_track"] = (int)($track["mouse_track"] ?? 0);
            $track["mouse_clicks"] = (int)($track["mouse_clicks"] ?? 0);
            $track["scroll_track"] = (int)($track["scroll_track"] ?? 0);
            $ret .= "\n" . $track["href"] . " [d:" . (int)$track["duration"] . "s mt:{$track["mouse_track"]} st:{$track["scroll_track"]} mc:{$track["mouse_clicks"]} c:" . implode(",", array_keys($track["conversions"] ?? [])) . "]";
        }
        return $ret;
    }


}
