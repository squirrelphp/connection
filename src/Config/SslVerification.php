<?php

namespace Squirrel\Connection\Config;

enum SslVerification
{
    case None;
    case Ca;
    case CaAndHostname;
}
