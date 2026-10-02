<?php 
require_once 'config.php';

verificarLogin();

if(isset($_GET['logout'])){
    session_destroy(); 
    header('Location: loguin.php');
    exit();
}

?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almoxarifado Industrial</title>
</head>

<body>
    <h1>Almoxarifado Industrial</h1>
    <div>
        <p>Usuário logado: <?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?>
            <a href="?logout=1">Sair</a>
        </p>
    </div>
    <hr>
    <h2>Menu</h2>
    <ul>
        <li><a href="produtos.php">Cadastro de insumos</a></li>
        <li><a href="estoque.php">Gestão de estoque</a></li>
    </ul>
</body>

</html>