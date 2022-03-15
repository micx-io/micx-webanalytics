<?php

namespace Micx\FormMailer\Stats;

class Report
{

    /**
     * @var Visitor[]
     */
    public $visitorMap = [];

    public $conversions = [];

    public function inject (array $data)
    {
        if ( ! isset ($this->visitorMap[$data["session_id"]])) {
            $this->visitorMap[$data["session_id"]] = new Visitor(
                $data["ts"], $data["visitor_id"], $data["ip"], $data["host"], $data["visitor_id_gmdate"], $data["last_visit_gmdate"],
                $data["visits"], $data["language"], $data["user_agent"], $data["screen"], $data["session_id"], $data["href"]
            );
        }

        foreach ($data["conversions"] ?? [] as $key => $val) {
            if ( ! isset ($this->conversions[$key]))
                $this->conversions[$key] = 0;
            $this->conversions[$key]++;
        }

        $this->visitorMap[$data["session_id"]]->inject($data);
    }


    public function getReport() : string
    {
        $ret = "Daily report on conversions:";
        $ret .= "\n--------------------------------";
        foreach ($this->conversions as $key => $val)
            $ret .= "\n$key: $val";
        $ret .= "\n\n";
        foreach ($this->visitorMap as $key => $val) {
            $ret .= "\n" . $val->getReport();
        }
        return $ret;
    }

}
