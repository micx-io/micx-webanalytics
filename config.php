<?php

define("DEV_MODE", (bool)"1");
define("DATA_PATH", "/data");
define("FE_SECRET", "XXXXX");


if (DEV_MODE === true) {
    define("CONFIG_PATH", "/opt/cfg");
} else {
    define("CONFIG_PATH", "/config");
}


