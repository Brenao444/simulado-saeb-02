<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = '';

if (isset($_GET['excluir'])) {
    try {
        $conn->prepare("DELETE FROM insumos WHERE id = ?")
            ->execute([(int)$_GET['excluir']]);
        $mensagem = "Insumo excluído com sucesso.";
        $tipoMensagem = 'sucesso';
    } catch (PDOException $e) {
        $mensagem = "Não foi possível excluir o insumo. Verifique se há movimentações vinculadas.";
        $tipoMensagem = 'erro';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;

    $data = [
        'codigo' => trim($_POST['codigo'] ?? ''),
        'nome' => trim($_POST['nome'] ?? ''),
        'categoria' => trim($_POST['categoria'] ?? ''),
        'unidade_medida' => trim($_POST['unidade_medida'] ?? ''),
        'custo' => str_replace(',', '.', trim($_POST['custo'] ?? '')),
        'estoque_atual' => trim($_POST['estoque_atual'] ?? ''),
        'estoque_minimo' => trim($_POST['estoque_minimo'] ?? ''),
        'ativo' => (int)($_POST['ativo'] ?? 1)
    ];

    try {
        if (
            !$data['codigo'] || !$data['nome'] || !$data['categoria'] || !$data['unidade_medida'] ||
            !is_numeric($data['custo']) || $data['custo'] < 0 ||
            filter_var($data['estoque_atual'], FILTER_VALIDATE_INT) === false || $data['estoque_atual'] < 0 ||
            filter_var($data['estoque_minimo'], FILTER_VALIDATE_INT) === false || $data['estoque_minimo'] < 0 ||
            !in_array($data['ativo'], [0, 1], true)
        ) {
            $mensagem = 'Informe os campos obrigatórios e use valores válidos para custo e estoque.';
            $tipoMensagem = 'erro';
        } else {
            if ($id > 0) {
                $sql = "UPDATE insumos SET
                    codigo=:codigo,
                    nome=:nome,
                    categoria=:categoria,
                    unidade_medida=:unidade_medida,
                    custo=:custo,
                    estoque_atual=:estoque_atual,
                    estoque_minimo=:estoque_minimo,
                    ativo=:ativo
                    WHERE id =:id";
                $data['id'] = $id;
            } else {
                $sql = "INSERT INTO insumos (
                    codigo, nome, categoria, unidade_medida, custo,
                    estoque_atual, estoque_minimo, ativo
                    ) VALUES (
                    :codigo, :nome, :categoria, :unidade_medida, :custo,
                    :estoque_atual, :estoque_minimo, :ativo)";
            }
            $conn->prepare($sql)->execute($data);
            $mensagem = $id > 0 ? 'Insumo atualizado.' : 'Insumo cadastrado.';
            $tipoMensagem = 'sucesso';
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao salvar o produto: " . $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

$produtosEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM insumos WHERE id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $produtosEditar = $stmt->fetch();
}

$busca = trim($_GET['busca'] ?? '');
$sql = "SELECT * FROM insumos WHERE ativo = 1" . ($busca ? " AND (nome LIKE :b OR codigo LIKE :b OR categoria LIKE :b)" : "") . " ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->execute($busca ? ['b' => "%$busca%"] : []);
$produtos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Insumos - Indústria</title>
</head>

<body>
    <h1>Cadastro de Insumos</h1>
    <p><a href="index.php">Voltar ao início</a></p>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <h2><?= $produtosEditar ? 'Editar' : 'Novo' ?> insumo</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $produtosEditar['id'] ?? '' ?>">
        
        <label>Código: <input type="text" name="codigo" required value="<?= htmlspecialchars($produtosEditar['codigo'] ?? '') ?>"></label><br>
        <label>Nome do insumo: <input type="text" name="nome" size="40" required value="<?= htmlspecialchars($produtosEditar['nome'] ?? '') ?>"></label><br>
        <label>Categoria: <input type="text" name="categoria" required value="<?= htmlspecialchars($produtosEditar['categoria'] ?? '') ?>"></label><br>
        <label>Unidade de medida: <input type="text" name="unidade_medida" placeholder="unidade, kg, litro..." required value="<?= htmlspecialchars($produtosEditar['unidade_medida'] ?? '') ?>"></label><br>
        <label>Custo unitário (R$): <input type="number" name="custo" min="0" step="0.01" required value="<?= htmlspecialchars($produtosEditar['custo'] ?? '') ?>"></label><br>
        <label>Estoque atual: <input type="number" min="0" name="estoque_atual" required value="<?= htmlspecialchars($produtosEditar['estoque_atual'] ?? 0) ?>"></label><br>
        <label>Estoque mínimo: <input type="number" min="0" name="estoque_minimo" required value="<?= htmlspecialchars($produtosEditar['estoque_minimo'] ?? 0) ?>"></label><br>
        <label>Status:
            <select name="ativo">
                <option value="1" <?= ($produtosEditar['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
                <option value="0" <?= ($produtosEditar['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
            </select>
        </label><br>
        <br>

        <button type="submit">Salvar</button>
        <?php if ($produtosEditar): ?>
            <a href="produtos.php"><button type="button">Cancelar</button></a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Lista de insumos</h2>
    <form method="GET">
        <label>Buscar: <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome, código ou categoria"></label>
        <button type="submit">Buscar</button>
        <?php if ($busca): ?> <a href="produtos.php">Limpar</a> <?php endif; ?>
    </form>
    <br>

    <?php if ($produtos): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Unidade</th>
                    <th>Custo unitário</th>
                    <th>Estoque Atual</th>
                    <th>Estoque Mínimo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $p): ?> 
                    <tr>
                        <td><?= htmlspecialchars($p['codigo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['unidade_medida'] ?? '') ?></td>
                        <td>R$ <?= number_format($p['custo'], 2, ',', '.') ?></td>
                        <td><?= $p['estoque_atual'] ?></td>
                        <td><?= $p['estoque_minimo'] ?></td>
                        <td><?= $p['ativo'] == 1 ? 'Ativo' : 'Inativo' ?></td>
                        <td>
                            <a href="produtos.php?editar=<?= $p['id'] ?>">Editar</a> |
                            <a href="produtos.php?excluir=<?= $p['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table> 
    <?php else: ?>
        <p>Nenhum insumo encontrado.</p>
    <?php endif; ?>
</body>

</html>