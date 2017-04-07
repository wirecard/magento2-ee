<?php
$dummyVarEnv = getenv('DUMMY_VAR');
echo "Value of DUMMY_VAR:\n";
var_dump($dummyVarEnv);
$authData = json_decode($dummyVarEnv, true);
echo "Converted into array with json_decode:\n";
var_dump($authData);
