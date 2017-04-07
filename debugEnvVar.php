<?php
$dummyVarEnv = getenv('DUMMY_VAR');
var_dump($dummyVarEnv);
$authData = json_decode($dummyVarEnv, true);
var_dump($authData);
