<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
verificaPerfil(['ADMIN']);

// salvar
if ($_POST) {
    $nome   = $_POST['nome'];
    $email  = $_POST['email'];
    $perfil = $_POST['perfil'];
    $senha  = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $sql = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, perfil)
        VALUES (?, ?, ?, ?)
    ");
    $sql->execute([$nome, $email, $senha, $perfil]);
}

// listar
$usuarios = $pdo->query("SELECT id, nome, email, perfil, ativo FROM usuarios")->fetchAll();
?>

<h2>Usuários</h2>

<form method="post">
    <input name="nome" placeholder="Nome" required>
    <input name="email" type="email" placeholder="Email" required>
    <input name="senha" type="password" placeholder="Senha" required>

    <select name="perfil" required>
        <option value="ADMIN">Administrador</option>
        <option value="OPERADOR">Operador</option>
        <option value="CONSULTA">Consulta</option>
    </select>

    <button type="submit">Salvar</button>
</form>

<hr>

<table border="1" cellpadding="5">
<tr>
    <th>Nome</th>
    <th>Email</th>
    <th>Perfil</th>
    <th>Status</th>
    <th>Ações</th>
</tr>

<?php foreach ($usuarios as $u): ?>
<tr>
    <td><?= $u['nome'] ?></td>
    <td><?= $u['email'] ?></td>
    <td><?= $u['perfil'] ?></td>
    <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
    <td>
        <a href="usuario_toggle.php?id=<?= $u['id'] ?>">
            <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</table>
