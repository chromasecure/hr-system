<?php
$password = 'secret123';

// generate new hash
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "HASH: $hash\n<br>";

// verify just to be sure
var_dump(password_verify($password, $hash));
