<?php

define("DEV_MODE", (bool)"1");
define("DATA_PATH", "/data");
define("FE_SECRET", "XXXXX");

define("CONF_SUBSCRIPTION_ENDPOINT", "/opt/mock/sub");
define("CONF_SUBSCRIPTION_CLIENT_ID", "micx-webanalytics");
define("CONF_SUBSCRIPTION_CLIENT_SECRET", "");


if (DEV_MODE === true) {
    define("CONFIG_PATH", "/opt/cfg");
} else {
    define("CONFIG_PATH", "/config");
}


