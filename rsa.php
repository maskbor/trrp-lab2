
<?php

function genKeys()
{

    $keyResource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    openssl_pkey_export(
        $keyResource,
        $privateKey
    );

    // сохраняем приватный ключ в файл
    file_put_contents(
        'private-key.pem',
        $privateKey
    );

    // получаем публичный ключ
    $publicKey = openssl_pkey_get_details($keyResource);

    // сохраняем публичный ключ в файл
    file_put_contents(
        'public-key.pem',
        $publicKey['key']
    );
}

function rsaEncode($publicKey, $data)
{
    $publicKey = openssl_pkey_get_public($publicKey);

    openssl_public_encrypt(
        $data,
        $encrypted,
        $publicKey
    );

    openssl_free_key($publicKey);

    return base64_encode($encrypted);
}

function rsaDecode($privateKey, $encrypted)
{
    $privateKey = openssl_pkey_get_private($privateKey);

    $encrypted = base64_decode($encrypted);

    openssl_private_decrypt(
        $encrypted,
        $decrypted, 
        $privateKey
    );

    openssl_free_key($privateKey);

    return $decrypted;
}
?>
