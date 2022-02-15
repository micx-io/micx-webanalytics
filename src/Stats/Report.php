<?php

namespace Micx\FormMailer\Stats;

class Report
{

    /**
     * @var Visitor[]
     */
    public $visitorMap = [];


    public function inject (array $data)
    {
        if ( ! isset ($this->visitorMap[$data["visitor_id"]])) {
            $this->visitorMap[$data["visitor_id"]] = new Visitor(
                $data["ts"], $data["visitor_id"], $data["ip"], $data["host"], $data["visitor_id_gmdate"], $data["last_visit_gmdate"],
                $data["visits"], $data["language"], $data["user_agent"], $data["screen"]
            );
        }
        $this->visitorMap[$data["visitor_id"]]->inject($data);
    }


    public function getReport() : string
    {
        $ret = "";
        foreach ($this->visitorMap as $key => $val) {
            $ret .= "\n" . $val->getReport();
        }
        return $ret;
    }

}
