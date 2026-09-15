<?php
session_start();

// Configuração do Banco de Dados SQLite
try {
    $db = new PDO('sqlite:apicultura_site.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS recados (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        conteudo TEXT NOT NULL,
        arquivo TEXT,
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

$senha_mestra = "mel2026";
$erro_login = false;

// Processar Login do Professor Laurielson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'login') {
    if (trim($_POST['senha']) === $senha_mestra) {
        $_SESSION['admin_apicultura'] = true;
        header("Location: " . $_SERVER['PHP_SELF'] . "#painel-professor");
        exit;
    } else {
        $erro_login = true;
    }
}

// Processar Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_apicultura']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Excluir Recado (Somente Professor)
if (isset($_GET['excluir']) && isset($_SESSION['admin_apicultura'])) {
    $id = (int)$_GET['excluir'];
    $stmt = $db->prepare("SELECT arquivo FROM recados WHERE id = ?");
    $stmt->execute([$id]);
    $recado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($recado && $recado['arquivo'] && file_exists("uploads/" . $recado['arquivo'])) {
        unlink("uploads/" . $recado['arquivo']);
    }
    
    $stmt = $db->prepare("DELETE FROM recados WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: " . $_SERVER['PHP_SELF'] . "#mural");
    exit;
}

// Publicar Novo Recado (Somente Professor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar' && isset($_SESSION['admin_apicultura'])) {
    $titulo = trim($_POST['titulo']);
    $conteudo = trim($_POST['conteudo']);
    $nome_arquivo = null;

    if (!empty($_FILES['arquivo']['name'])) {
        $diretorio = "uploads/";
        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
        $nome_arquivo = time() . "_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['arquivo']['tmp_name'], $diretorio . $nome_arquivo);
    }

    if ($titulo && $conteudo) {
        $stmt = $db->prepare("INSERT INTO recados (titulo, conteudo, arquivo) VALUES (?, ?, ?)");
        $stmt->execute([$titulo, $conteudo, $nome_arquivo]);
        header("Location: " . $_SERVER['PHP_SELF'] . "#mural");
        exit;
    }
}

$recados = $db->query("SELECT * FROM recados ORDER BY data_criacao DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laurielson Alencar | Apicultura & Pesquisa</title>
    <style>
        :root {
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --bg: #fffbeb;
            --surface: #ffffff;
            --text-dark: #1f2937;
            --text-muted: #4b5563;
            --accent: #78350f;
            --border: #fef3c7;
        }

        * { box-sizing: border-box; font-family: system-ui, -apple-system, sans-serif; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background-color: var(--bg); color: var(--text-dark); line-height: 1.6; word-wrap: break-word; }

        /* Barra Administrativa */
        .admin-bar {
            background: #111827; color: #fff; padding: 0.6rem 1rem;
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.85rem; position: sticky; top: 0; z-index: 200; flex-wrap: wrap; gap: 0.5rem;
        }
        .admin-bar a { color: #f59e0b; text-decoration: none; font-weight: 600; }

        /* Navegação Responsiva */
        nav {
            position: sticky; top: <?= isset($_SESSION['admin_apicultura']) ? '38px' : '0' ?>; 
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px);
            border-bottom: 1px solid #f3f4f6; z-index: 100; padding: 0.8rem 0;
        }
        .nav-container {
            max-width: 900px; margin: 0 auto; padding: 0 1rem;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;
        }
        .logo { font-weight: 800; color: var(--accent); text-decoration: none; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem; }
        .nav-links { display: flex; gap: 1rem; flex-wrap: wrap; }
        .nav-links a { text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; padding: 0.2rem 0; }
        .nav-links a:hover { color: var(--primary-hover); }

        /* Hero Adaptável */
        .hero {
            position: relative; text-align: center; padding: 4rem 1rem 3rem 1rem; color: #ffffff;
            background: linear-gradient(180deg, rgba(120, 53, 15, 0.88) 0%, rgba(245, 158, 11, 0.8) 100%), 
                        url('https://images.unsplash.com/photo-1587049352847-4a222e784d38?auto=format&fit=crop&w=1350&q=80') center/cover no-repeat;
        }
        .hero-badge { background: rgba(255, 255, 255, 0.2); color: #fff; padding: 0.3rem 0.8rem; border-radius: 99px; font-size: 0.8rem; font-weight: 600; display: inline-block; margin-bottom: 0.8rem; backdrop-filter: blur(4px); }
        .hero h1 { font-size: 2.2rem; margin: 0.4rem 0; font-weight: 800; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .hero p { font-size: 1rem; max-width: 650px; margin: 0.5rem auto 0 auto; opacity: 0.95; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }

        /* Estrutura Central */
        .container { max-width: 900px; margin: 0 auto; padding: 2rem 1rem 3rem 1rem; }
        section { margin-bottom: 2.5rem; }
        .section-title { font-size: 1.25rem; color: var(--accent); margin-bottom: 1rem; border-bottom: 2px solid var(--border); padding-bottom: 0.4rem; }
        
        .card { background: var(--surface); border-radius: 12px; padding: 1.4rem; border: 1px solid #f3f4f6; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }

        .grid-projetos { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; }
        .projeto-item { background: var(--surface); border-radius: 10px; padding: 1.2rem; border: 1px solid #f3f4f6; }
        .projeto-item h3 { margin-top: 0; color: var(--primary-hover); font-size: 1.05rem; }

        .recado-card { background: var(--surface); border-radius: 10px; padding: 1.2rem; margin-bottom: 1rem; border-left: 4px solid var(--primary); }
        .recado-meta { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem; }
        .media-preview { max-width: 100%; height: auto; border-radius: 8px; margin-top: 0.8rem; display: block; }

        /* Botões e Inputs Amigáveis ao Toque */
        .btn { background: var(--primary); color: white; border: none; padding: 0.75rem 1.2rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; cursor: pointer; text-align: center; width: auto; }
        .btn:hover { background: var(--primary-hover); }
        .btn-insta { background: #e1306c; }
        .btn-whatsapp { background: #25d366; }
        .btn-danger { background: #ef4444; font-size: 0.8rem; padding: 0.5rem 0.8rem; }

        .botoes-contato { display: flex; justify-content: center; gap: 0.8rem; margin-top: 1.2rem; flex-wrap: wrap; }

        input[type="text"], input[type="password"], textarea { width: 100%; padding: 0.75rem; margin-top: 0.3rem; margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 1rem; }

        .secret-trigger { cursor: pointer; opacity: 0.25; user-select: none; font-size: 1.2rem; padding: 0.4rem; }
        .secret-trigger:hover { opacity: 1; }

        /* Ajustes Específicos para Telas Pequenas (Smartphones) */
        @media (max-width: 600px) {
            .nav-container { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
            .nav-links { width: 100%; justify-content: space-between; border-top: 1px solid #f3f4f6; padding-top: 0.5rem; }
            .hero { padding: 3rem 1rem 2.5rem 1rem; }
            .hero h1 { font-size: 1.75rem; }
            .hero p { font-size: 0.95rem; }
            .botoes-contato .btn { width: 100%; }
            .container { padding: 1.5rem 0.8rem 2.5rem 0.8rem; }
        }
    </style>
</head>
<body>

    <!-- BARRA DO PROFESSOR (Logado) -->
    <?php if (isset($_SESSION['admin_apicultura'])): ?>
        <div class="admin-bar">
            <span>🐝 Painel de Laurielson Alencar</span>
            <a href="?logout=1" style="color: #ef4444;">← Sair</a>
        </div>
    <?php endif; ?>

    <!-- NAVEGAÇÃO -->
    <nav>
        <div class="nav-container">
            <a href="#" class="logo">
                <span>🐝</span> Laurielson Alencar
            </a>
            <div class="nav-links">
                <a href="#sobre">Sobre</a>
                <a href="#projetos">Atuação</a>
                <a href="#mural">Mural</a>
                <a href="#contato">Contato</a>
            </div>
        </div>
    </nav>

    <!-- TOPO HERO COM ABELHAS E DEGRADÊ -->
    <header class="hero">
        <span class="hero-badge">Professor & Pesquisador em Apicultura</span>
        <h1>Laurielson Alencar</h1>
        <p>Projetos de ensino, extensão, pesquisas apícolas e avisos oficiais para alunos e comunidade.</p>
    </header>

    <div class="container">

        <!-- PAINEL DE POSTAGEM DO PROFESSOR -->
        <?php if (isset($_SESSION['admin_apicultura'])): ?>
            <section id="painel-professor" style="border: 2px dashed var(--primary); padding: 1.2rem; border-radius: 12px; background: #fff; margin-bottom: 2rem;">
                <h3 style="margin-top: 0; color: var(--accent);">📌 Nova Publicação no Mural</h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="salvar">
                    
                    <label><strong>Título:</strong></label>
                    <input type="text" name="titulo" placeholder="Ex: Cronograma de Aulas no Apiário" required>

                    <label><strong>Conteúdo do Aviso:</strong></label>
                    <textarea name="conteudo" rows="4" placeholder="Escreva a mensagem aqui..." required></textarea>

                    <label><strong>Anexar Foto ou Documento (Opcional):</strong></label><br>
                    <input type="file" name="arquivo" style="margin-top: 0.3rem; margin-bottom: 1rem; width: 100%;"><br>

                    <button type="submit" class="btn" style="width: 100%;">Publicar no Mural Público</button>
                </form>
            </section>
        <?php endif; ?>

        <!-- 1. SOBRE -->
        <section id="sobre">
            <h2 class="section-title">Sobre o Professor</h2>
            <div class="card">
                <p><strong>Laurielson Alencar</strong> é professor e pesquisador dedicado ao desenvolvimento técnico e científico da apicultura. Desenvolve atividades voltadas ao manejo produtivo de colmeias, conservação de abelhas e inovação em produtos apícolas.</p>
                <p>Este portal tem como objetivo servir de canal de comunicação direto com os alunos, divulgando materiais didáticos, resultados de estudos e avisos de aula.</p>
            </div>
        </section>

        <!-- 2. ATUAÇÃO E PROJETOS -->
        <section id="projetos">
            <h2 class="section-title">Atuação & Projetos</h2>
            <div class="grid-projetos">
                <div class="projeto-item">
                    <h3>🔬 Manejo & Pesquisa</h3>
                    <p>Desenvolvimento de técnicas de nutrição e manejo integrado para o fortalecimento do apiário.</p>
                </div>
                <div class="projeto-item">
                    <h3>📚 Ensino & Extensão</h3>
                    <p>Orientação acadêmica, cursos práticos e capacitação sobre produtos da colmeia.</p>
                </div>
                <div class="projeto-item">
                    <h3>🍯 Consultoria Técnica</h3>
                    <p>Apoio técnico para produtores locais na melhoria da produção de mel e própolis.</p>
                </div>
            </div>
        </section>

        <!-- 3. MURAL PÚBLICO -->
        <section id="mural">
            <h2 class="section-title">Mural de Avisos & Publicações</h2>

            <?php if (empty($recados)): ?>
                <div class="card"><p style="color: var(--text-muted); margin: 0;">Nenhum aviso publicado até o momento.</p></div>
            <?php else: ?>
                <?php foreach ($recados as $r): ?>
                    <article class="recado-card">
                        <h3 style="margin: 0; color: var(--accent); font-size: 1.1rem;"><?= htmlspecialchars($r['titulo']) ?></h3>
                        <div class="recado-meta">Publicado por Laurielson Alencar em <?= date('d/m/Y \à\s H:i', strtotime($r['data_criacao'])) ?></div>
                        <p style="margin-top: 0.5rem; margin-bottom: 0.5rem;"><?= nl2br(htmlspecialchars($r['conteudo'])) ?></p>

                        <?php if ($r['arquivo']): ?>
                            <?php 
                            $ext = strtolower(pathinfo($r['arquivo'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): 
                            ?>
                                <img src="uploads/<?= htmlspecialchars($r['arquivo']) ?>" alt="Mídia anexada" class="media-preview">
                            <?php else: ?>
                                <a href="uploads/<?= htmlspecialchars($r['arquivo']) ?>" target="_blank" class="btn" style="background: #e5e7eb; color: var(--text-dark); font-size: 0.85rem; margin-top: 0.5rem;">📄 Baixar Anexo</a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['admin_apicultura'])): ?>
                            <div style="margin-top: 0.8rem;">
                                <a href="?excluir=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Excluir este aviso?');">Excluir Aviso</a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- 4. CONTATO -->
        <section id="contato">
            <h2 class="section-title">Contato</h2>
            <div class="card" style="text-align: center;">
                <p>Para dúvidas sobre disciplinas, projetos ou consultoria apícola, entre em contato:</p>
                <div class="botoes-contato">
                    <a href="https://www.instagram.com/laurielsonalencar?utm_source=ig_web_button_share_sheet&stkn=ZDNlZDc0MzIxNw==" target="_blank" class="btn btn-insta">📷 Instagram</a>
                    <a href="https://wa.me/" target="_blank" class="btn btn-whatsapp">💬 WhatsApp Direto</a>
                </div>
            </div>
        </section>

    </div>

    <!-- RODAPÉ PÚBLICO -->
    <footer style="text-align: center; padding: 2rem 1rem; background: #fff; border-top: 1px solid #f3f4f6; color: var(--text-muted); font-size: 0.85rem;">
        <p>&copy; <?= date('Y') ?> Laurielson Alencar. Todos os direitos reservados.</p>
        
        <?php if (!isset($_SESSION['admin_apicultura'])): ?>
            <details style="display: inline-block; margin-top: 0.5rem;">
                <summary class="secret-trigger" title="Acesso do Professor">🐝</summary>
                <form action="" method="POST" style="margin-top: 0.8rem; display: flex; gap: 0.4rem; justify-content: center; flex-wrap: wrap;">
                    <input type="hidden" name="acao" value="login">
                    <input type="password" name="senha" placeholder="Senha do Professor" required style="width: 150px; padding: 0.4rem; font-size: 0.8rem; margin: 0;">
                    <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Entrar</button>
                </form>
                <?php if ($erro_login): ?>
                    <p style="color: #ef4444; font-size: 0.75rem; margin-top: 0.4rem;">Senha incorreta! Tente novamente.</p>
                <?php endif; ?>
            </details>
        <?php endif; ?>
    </footer>

</body>
</html>
