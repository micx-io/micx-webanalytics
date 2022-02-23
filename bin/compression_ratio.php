<?php
require __DIR__ . "/../vendor/autoload.php";


$pages = [
    "https://de.wikipedia.org/wiki/Gastornis",
    "https://de.wikipedia.org/wiki/Apolo_Anton_Ohno",
    "https://de.wikipedia.org/wiki/Europ%C3%A4isches_Nordmeer",
    "https://de.wikipedia.org/wiki/Geschichte_der_Stadt_Mainz",
    "https://de.wikipedia.org/wiki/Polnisch-Sowjetischer_Krieg",

    "https://de.wikipedia.org/wiki/Kernenergie",
    "https://de.wikipedia.org/wiki/Scientology",
    "https://de.wikipedia.org/wiki/Siemens",
    "https://de.wikipedia.org/wiki/Wikipedia"

];

foreach ($pages as $page) {
    $content = phore_http_request($page)->send()->getBody();
    $data = strip_tags($content);

    $ratio = number_format((strlen(gzcompress($data)) / strlen($data)) * 100, 2);

    echo "\n" . str_pad($page, 60) . "[". number_format(strlen($data)/1000) . "kb] " . $ratio . "%";

}
echo "\n";

