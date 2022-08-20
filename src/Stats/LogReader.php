<?php

namespace Micx\FormMailer\Stats;

use Phore\FileSystem\PhoreFile;

class LogReader
{

    public function read(PhoreFile $file) {
        if ( ! $file->exists())
            return "";

        $stream = $file->fopen("r");
        $ret = "";
        while ( ! $stream->feof()) {
            try {

                $data = phore_json_decode($stream->fgets());
                if (((int)$data["ts"]) < strtotime("-1 day"))
                    continue;

                $ret .= "\n{$data["time"]} {$data["ip"]} {$data["href"]} {$data["host"]} {$data["user_agent"]}";
            } catch (\Exception $e) {
                continue;
            }
        }
        return $ret;
    }
}
