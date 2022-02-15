<?php

namespace Micx\FormMailer\Stats;

use Micx\FormMailer\Config\Config;
use Phore\FileSystem\PhoreFile;
use Phore\Mail\PhoreMailer;

class FileStatsRunner
{

    public function __construct (

    ){}


    public function run(PhoreFile $inFile, int $fromTs, int $tillTs) : Report
    {
        $reader = $inFile->fopen("r");
        $report = new Report();

        while ( ! $reader->feof()) {
            $data = json_decode($reader->fgets(), true);
            if ( ! is_array($data))
                continue;
            $ts = (int)($data["ts"] ?? 0);
            if ($ts < $fromTs)
                continue;
            if ($ts > $tillTs)
                return $report;
            try {
                $report->inject($data);
            } catch (\Error $e) {
                echo "\nError processing line: " . $e->getMessage();
            }

        }
        $reader->fclose();
        return $report;
    }

    public function runAll($dayOffset = 0)
    {
        $fromTs = strtotime(date ("Y-m-d 00:00:00", strtotime("-$dayOffset Day")));
        $tillTs = strtotime(date ("Y-m-d 23:59:59", strtotime("-$dayOffset Day")));
        $mailer = new PhoreMailer();
        $mailer->setSmtpDirectConnect("micx.io");
        $template = file_get_contents(__DIR__ . "/../mail.txt");

        foreach (phore_dir(CONFIG_PATH)->genWalk("*.yml") as $configFile) {
            $subscriptionId = $configFile->getFilename();
            $config = phore_hydrate($configFile->assertFile()->get_yaml(), Config::class);

            $logfile = phore_file(DATA_PATH . "/$subscriptionId");

            if ( ! $logfile->exists())
                continue;

            $report = $this->run($logfile, $fromTs, $tillTs);

            $mailer->send($template, [
                "email" => $config->report_email,
                "subscription_id" => $subscriptionId,
                "data" => $report->getReport(),
                "total" => count ($report->visitorMap)
            ]);

        }
    }

}
