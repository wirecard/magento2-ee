<?php
$composerAuthEnv = getenv('COMPOSER_AUTH'));
var_dump($composerAuthEnv);
$authData = json_decode($composerAuthEnv, true);
var_dump($authData);
