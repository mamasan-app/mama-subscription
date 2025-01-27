<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=mamasubscription', 'root', 'Mojeda201102$');
    echo 'Conexión exitosa a la base de datos';
} catch (PDOException $e) {
    echo 'Error al conectar: '.$e->getMessage();
}
