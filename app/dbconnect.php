<?php
try {
    $connexion = new PDO("mysql:host=localhost;dbname=vote;charset=utf8", "root", "");
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    printf("Échec de la connexion: %s\n", $e->getMessage());
    exit();
}


?>
