<?php
/**
 * Desencripta, los hash de error en los .log's
 */
CONST CRYPT_SECRET = 'IronErrorCryptSecret';

$encrypted = $argv[1];

$string = mcrypt_decrypt(
    MCRYPT_RIJNDAEL_256,
    md5(CRYPT_SECRET),
    base64_decode(urldecode($encrypted)),
    MCRYPT_MODE_CBC,
    md5(md5(CRYPT_SECRET))
);

echo "\n##################################################################\n";
echo "\n" . rtrim($string, "\0") . "\n";
echo "\n##################################################################\n\n";