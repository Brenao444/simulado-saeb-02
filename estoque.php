<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = $alertaEstoque = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $insumoId = (int)($_POST['insumo_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? "";
    $qtd = (int)($_POST['quantidade'] ?? 0);
    $data = $_POST['data_movimentacao'] ?? "";
    $observacao = trim($_POST['observacao'] ?? '');
    $dataValidada = DateTime::createFromFormat('!Y-m-d', $data);

    if (!$insumoId || !in_array($tipo, ['ENTRADA', 'SAIDA'], true) || !$dataValidada || $dataValidada->format('Y-m-d') !== $data || $qtd < 1) {
        $mensagem = "Preencha todos os campos!";
        $tipoMensagem = 'erro';
    } else {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("SELECT * FROM insumos WHERE id = ? AND ativo = 1 FOR UPDATE");
            $stmt->execute([$insumoId]);
            $insumo = $stmt->fetch();

            if (!$insumo) {
                throw new RuntimeException('Insumo não encontrado ou inativo.');
            }

            $anterior = (int)$insumo['estoque_atual'];
            $novo = $tipo === 'ENTRADA' ? $anterior + $qtd : $anterior - $qtd;
            if ($novo < 0) {
                throw new RuntimeException('Estoque insuficiente para registrar essa saída.');
            }

            $conn->prepare("INSERT INTO movimentacoes (tipo, data_movimentacao, quantidade, saldo_anterior, saldo_posterior, observacao, usuarios_id, insumos_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$tipo === 'ENTRADA' ? 1 : 2, $data, $qtd, $anterior, $novo, $observacao ?: null, $_SESSION['usuario_id'], $insumoId]);
            $conn->prepare("UPDATE insumos SET estoque_atual = ? WHERE id = ?")->execute([$novo, $insumoId]);
            $conn->commit();

            $mensagem = "Movimentação registrada com sucesso.";
            $tipoMensagem = 'sucesso';
            if ($tipo === 'SAIDA' && $novo <= $insumo['estoque_minimo']) {
                $alertaEstoque = "ALERTA: {$insumo['nome']} atingiu o estoque mínimo ou ficou abaixo dele. Saldo: {$novo} {$insumo['unidade_medida']}; mínimo: {$insumo['estoque_minimo']}.";
            }
        } catch (RuntimeException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $mensagem = $e->getMessage();
            $tipoMensagem = 'erro';
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $mensagem = 'Erro ao registrar movimentação.';
            $tipoMensagem = 'erro';
        }
    }
}
$insumos = $conn->query("SELECT * FROM insumos WHERE ativo = 1 ORDER BY nome")->fetchAll();
$movimentacoes = $conn->query("SELECT m.*, i.nome AS insumo_nome, i.unidade_medida, u.nome AS usuario_nome FROM movimentacoes m JOIN insumos i ON i.id = m.insumos_id JOIN usuarios u ON u.id = m.usuarios_id ORDER BY m.data_movimentacao DESC, m.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Estoque - Almoxarifado Industrial</title>
</head>

<body>
    <h1>Gestão de Estoque</h1>
    <p><a href="index.php">Voltar ao início</a></p>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($mensagem) ?>
        </p>
        <hr>
    <?php endif; ?>
    <?php if ($alertaEstoque): ?>
        <p style="color: #9a3412;">
            <?= htmlspecialchars($alertaEstoque) ?>
        </p>
        <hr>
    <?php endif; ?>

    <h2>Registrar movimentação</h2>
    <form method="post">
        <label>
            Insumo:
            <select name="insumo_id" required>
                <option value="">Selecione o insumo</option>
                <?php foreach ($insumos as $insumo): ?>
                    <option value="<?= $insumo['id'] ?>"><?= htmlspecialchars($insumo['nome']) ?> (saldo: <?= $insumo['estoque_atual'] ?> <?= htmlspecialchars($insumo['unidade_medida']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <br>
        <label>Tipo:
            <input type="radio" name="tipo" value="ENTRADA" required> Entrada (recebimento)
            <input type="radio" name="tipo" value="SAIDA" required> Saída (produção)
        </label>
        <br>
        <label>Quantidade:
            <input type="number" name="quantidade" min="1" required>
        </label>
        <br>
        <label>Data da movimentação:
            <input type="date" name="data_movimentacao" value="<?= date('Y-m-d') ?>" required>
        </label>
        <br>
        <label>Observação: <input type="text" name="observacao" maxlength="255"></label>
        <br>
        <br>
        <button type="submit">Registrar Movimentação</button>
    </form>

    <h2>Saldo dos insumos</h2>
    <?php if($insumos): ?>
        <table border="1">
            <tr>
                <th>Código</th>
                <th>Insumo</th>
                <th>Categoria</th>
                <th>Unidade</th>
                <th>Custo unitário</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Aviso</th>
            </tr>
            <?php foreach ($insumos as $p): ?>
                <tr>
                    <td> <?= htmlspecialchars($p['codigo']) ?> </td>
                    <td> <?= htmlspecialchars($p['nome']) ?> </td>
                    <td> <?= htmlspecialchars($p['categoria'] ?? '') ?> </td>
                    <td><?= htmlspecialchars($p['unidade_medida']) ?></td>
                    <td> R$ <?= number_format($p['custo'],2,',','.') ?> </td>
                    <td> <?= ($p['estoque_atual']) ?> </td>
                    <td> <?= ($p['estoque_minimo']) ?> </td>
                    <td>
                        <?= $p['estoque_atual'] <= $p['estoque_minimo']
                            ? '<strong>ALERTA: estoque no mínimo ou abaixo</strong>'
                                : 'Normal'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>         
        </table>  
    <?php else: ?>
        <p>Nenhum insumo ativo encontrado.</p>
    <?php endif; ?>

    <hr>
    <h2>Movimentações de Estoque</h2>
    <?php if($movimentacoes): ?>
        <table border="1">
            <tr>
                <th>Data</th>
                <th>Insumo</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Saldo Anterior</th>
                <th>Saldo Posterior</th>
                <th>Observação</th>
                <th>Usuário</th>
            </tr>
            <?php foreach ($movimentacoes as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['data_movimentacao']) ?></td>
                    <td><?= htmlspecialchars($m['insumo_nome']) ?></td>
                    <td><?= $m['tipo'] == 1 ? 'ENTRADA' : 'SAÍDA' ?></td>
                    <td><?= htmlspecialchars($m['quantidade']) ?></td>
                    <td><?= htmlspecialchars($m['saldo_anterior']) ?> <?= htmlspecialchars($m['unidade_medida']) ?></td>
                    <td><?= htmlspecialchars($m['saldo_posterior']) ?> <?= htmlspecialchars($m['unidade_medida']) ?></td>
                    <td><?= htmlspecialchars($m['observacao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($m['usuario_nome']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhuma movimentação encontrada.</p> 
    <?php endif; ?>
</body>

</html>