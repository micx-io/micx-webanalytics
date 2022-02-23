<?php


function getSiteDataStorePath(string $subscription_id, string $href, string $version) : \Phore\FileSystem\PhoreFile
{
    $hostId = explode("#", explode("?", $href)[0])[0];
    if (str_ends_with($hostId, "/"))
        $hostId = substr($hostId, 0, -1);

    $path = phore_dir(DATA_PATH)
        ->join_secure($subscription_id .".data", $hostId, $version . ".html")->asFile();

    return $path;
}
