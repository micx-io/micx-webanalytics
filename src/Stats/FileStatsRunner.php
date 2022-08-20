<?php

namespace Micx\FormMailer\Stats;

use Lack\Subscription\SubscriptionManagerInterface;
use Micx\FormMailer\Config\T_Analytics;
use Micx\FormMailer\Config\TAnalytics;
use Phore\FileSystem\PhoreFile;
use Phore\Mail\PhoreMailer;

class FileStatsRunner
{

    public function __construct (
        public SubscriptionManagerInterface $subscriptionManager
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

        foreach ($this->subscriptionManager->getSubscriptionsByClientId() as $curSubscriptionId) {
            $subscription = $this->subscriptionManager->getSubscriptionById($curSubscriptionId, true);
            $clientConfig = $subscription->getClientPrivateConfig(null, T_Analytics::class);
            assert($clientConfig instanceof T_Analytics);



            $basicReport = "";

            $logFile = phore_file(DATA_PATH . "/{$subscription->subscription_id}.log");
            $basicReport = (new LogReader())->read($logFile);

            $traceFile = phore_file(DATA_PATH . "/{$subscription->subscription_id}.track");
            $report = null;
            if ($traceFile->exists()) {
                $report = $this->run($traceFile, $fromTs, $tillTs);
            }




            $mailer->send($template, [
                "email" => $clientConfig->report_email,
                "subscription_id" => $subscription->subscription_id,
                "data" =>  $report?->getReport() ."\n\n". $basicReport ,
                "total" => count ($report?->visitorMap ?? [])
            ]);

        }
    }

}
