<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$error='';$catalog=catalog();
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        validate_token();$action=(string)($_POST['action']??'');
        if($action==='setup'){
            if(configured())throw new RuntimeException('Configuração inicial já concluída.');
            $p=(string)($_POST['password']??'');$q=(string)($_POST['confirm_password']??'');
            if(strlen($p)<12||strlen($p)>200||$p!==$q)throw new RuntimeException('A senha deve ter pelo menos 12 caracteres e coincidir com a confirmação.');
            save_config(['password_hash'=>password_hash($p,PASSWORD_DEFAULT),'created_at'=>gmdate('c')]);
            session_regenerate_id(true);$_SESSION['admin']=true;
            header('Location: index.php');exit;
        }
        if($action==='login'){
            if(!configured())throw new RuntimeException('Configure a senha administrativa antes de acessar.');
            $attempts=(int)($_SESSION['login_attempts']??0);$at=(int)($_SESSION['login_since']??time());
            if(time()-$at>900){$attempts=0;$at=time();}
            if($attempts>=8)throw new RuntimeException('Muitas tentativas. Tente novamente mais tarde nesta sessão.');
            if(!password_verify((string)($_POST['password']??''),(string)(config()['password_hash']??''))){$_SESSION['login_attempts']=$attempts+1;$_SESSION['login_since']=$at;throw new RuntimeException('Senha incorreta.');}
            session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['login_attempts']=0;
            header('Location: index.php');exit;
        }
        if($action==='logout'){
            $_SESSION=[];session_regenerate_id(true);header('Location: index.php');exit;
        }
    }catch(Throwable $e){$error=$e->getMessage();}
}
$logged=is_admin();$ready=configured();
$page=(string)($_GET['page']??'home');
$app=(string)($_GET['app']??'');
if(!isset($catalog[$app]))$app='';
$installed=0;foreach($catalog as $id=>$module){if(module_installed($id))$installed++;}
$modulesTotal=count($catalog);
function navhref(string $id):string{return 'index.php?app='.rawurlencode($id);}
?><!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="dark light"><title>Craft Suite · Estúdio Unificado</title>
<link rel="icon" type="image/svg+xml" href="assets/logo.svg">
<link rel="stylesheet" href="assets/app.css?v=2.0.0">
</head><body>
<?php if(!$ready||!$logged): ?>
<div class="auth-back"><div class="auth-visual"><div class="brand-mark big">✳</div><div class="eyebrow">UM ESPAÇO PARA CRIAR</div><h1>Seu estúdio completo.<br><span>Uma única interface.</span></h1><p>Uma central para seus documentos, design, imagens, vídeos, áudio e projetos 3D.</p><div class="auth-foot">CRAFT SUITE / SELF-HOSTED</div></div>
<div class="auth-form"><div class="auth-card"><div class="eyebrow"><?= !$ready ? 'CONFIGURAÇÃO INICIAL' : 'ACESSO ADMINISTRATIVO' ?></div><h2><?= !$ready ? 'Proteja seu estúdio' : 'Bem-vindo de volta' ?></h2><p class="muted"><?= !$ready ? 'Defina a senha do administrador. Ela será necessária para acessar seu estúdio e a biblioteca de arquivos.' : 'Entre para acessar os editores já incluídos no pacote.' ?></p>
<?php if($error): ?><div class="alert error"><?=esc($error)?></div><?php endif; ?>
<form method="post" class="auth-fields"><input type="hidden" name="csrf" value="<?=esc(token())?>"><input type="hidden" name="action" value="<?=$ready?'login':'setup'?>"><label>Senha administrativa<input type="password" name="password" minlength="<?=$ready?'1':'12'?>" autocomplete="<?=$ready?'current-password':'new-password'?>" required placeholder="Digite sua senha"></label><?php if(!$ready):?><label>Confirme a senha<input type="password" name="confirm_password" minlength="12" autocomplete="new-password" required placeholder="Confirme a senha"></label><?php endif;?><button class="btn primary wide" type="submit"><?=$ready?'Entrar no estúdio':'Criar acesso e continuar'?></button></form><small class="muted">A senha é armazenada com hash seguro. Os dados de trabalho permanecem nesta hospedagem ou no navegador.</small></div></div></div>
<?php else: ?>
<div class="app-layout">
<aside class="sidebar" id="sidebar"><a class="brand" href="index.php"><span class="brand-mark">✳</span><span class="brand-label"><b>Craft Suite</b><small>ESTÚDIO UNIFICADO</small></span></a>
<div class="side-menu"><div class="side-caption">WORKSPACE</div><a class="side-link <?=(!$app&&$page==='home')?'active':''?>" href="index.php"><span class="side-icon">▦</span>Visão geral</a><a class="side-link <?=($page==='files'&&!$app)?'active':''?>" href="index.php?page=files"><span class="side-icon">▤</span>Meus arquivos</a><div class="side-caption second">FERRAMENTAS</div>
<?php foreach(['Documentos','Design','Vídeo e áudio','3D e materiais'] as $cat):?><div class="side-group"><?=esc(strtoupper($cat))?></div><?php foreach($catalog as $id=>$m):if($m['category']!==$cat)continue;?><a href="<?=esc(navhref($id))?>" class="side-link tool-link <?=($app===$id)?'active':''?>"><img src="assets/icons/<?=esc($id)?>.png" alt="" loading="lazy"><span><?=esc($m['title'])?></span><?=module_installed($id)?'':'<span class="side-dot" title="Arquivo não encontrado"></span>'?></a><?php endforeach;endforeach; ?>
<div class="side-caption second">ADMINISTRAÇÃO</div><a class="side-link <?=($page==='manage'&&!$app)?'active':''?>" href="index.php?page=manage"><span class="side-icon">⚙</span>Componentes da suíte</a></div>
<div class="sidebar-bottom"><div class="sidebar-progress"><div class="progress-top"><span>Editores incluídos</span><b><?=$installed?>/<?=$modulesTotal?></b></div><div class="progress-bar"><span style="width:<?=round(100*$installed/$modulesTotal)?>%"></span></div></div><form method="post"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?=esc(token())?>"><button class="logout" type="submit">Sair do painel <span>↗</span></button></form></div></aside>
<div class="main-area"><header class="topbar"><div class="breadcrumb"><button id="menu-toggle" class="icon-btn mobile-only" type="button" aria-label="Menu">☰</button><a href="index.php" class="muted">Workspace</a><span class="crumb-sep">/</span><strong><?=esc($app?$catalog[$app]['title']:($page==='manage'?'Componentes':($page==='files'?'Arquivos':'Visão geral')))?></strong></div><div class="top-actions"><button class="icon-btn" id="theme-toggle" type="button" title="Alternar tema" aria-label="Alternar tema">◐</button><a class="top-manage" href="index.php?page=manage">Componentes</a><span class="user-avatar">AD</span></div></header>
<?php if($app):$m=$catalog[$app];$online=module_installed($app);?>
<div class="module-page"><div class="module-toolbar"><a class="back-link" href="index.php">← Início</a><img src="assets/icons/<?=esc($app)?>.png" alt=""><div class="module-heading"><h1><?=esc($m['title'])?></h1><span><?=esc($m['subtitle'])?></span></div><div class="module-tools"><?php if($online):?><a class="btn small subtle" target="_blank" rel="noopener noreferrer" href="modules/<?=esc($app)?>/index.html">Abrir em nova aba ↗</a><button class="btn small subtle" id="app-fullscreen" type="button">Tela cheia ⛶</button><?php else:?><span class="chip warn">Arquivos ausentes</span><a class="btn small primary" href="index.php?page=manage#<?=esc($app)?>">Verificar arquivos</a><?php endif;?></div></div>
<?php if($online):?><div class="frame-container" id="frame-container"><iframe id="app-frame" src="modules/<?=esc($app)?>/index.html" title="<?=esc($m['title'])?>" allow="fullscreen; clipboard-read; clipboard-write; microphone; camera; autoplay" allowfullscreen loading="eager"></iframe></div><?php else:?><div class="empty-module"><div class="empty-icon"><img src="assets/icons/<?=esc($app)?>.png" alt=""></div><h2>Arquivos do <?=esc($m['title'])?> não encontrados</h2><p><?=esc($m['description'])?></p><p>O pacote desta versão já inclui os editores compilados. Se este módulo estiver ausente, reextraia o ZIP completo preservando a estrutura de pastas.</p><a href="index.php?page=manage#<?=esc($app)?>" class="btn primary">Verificar componentes →</a></div><?php endif;?></div>
<?php elseif($page==='manage'): ?>
<main class="page-pad"><div class="page-head"><div><div class="eyebrow">PACOTE AUTÔNOMO</div><h1>Componentes da suíte</h1><p class="muted">Todos os editores web estão incluídos. Não há downloads, instalação de módulos ou compilação no servidor.</p></div><span class="count-pill"><?=$installed?> de <?=$modulesTotal?> disponíveis</span></div>
<div class="info-strip">ⓘ Basta extrair o pacote inteiro na <code>public_html</code> e acessar a aplicação via HTTPS. O servidor entrega PHP, JavaScript e WebAssembly já compilado. Cada editor mantém os próprios recursos e formatos de projeto.</div>
<div class="diagnostics"><span>PHP <b><?=esc(PHP_VERSION)?></b></span><span>WASM <b class="ok">Arquivos locais</b></span><span>Dados privados <b><?=is_writable(PRIVATE_ROOT)?'Graváveis':'Sem permissão'?></b></span><span>HTTPS <b class="<?=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'ok':'bad'?>"><?=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'Ativo':'Verificar configuração'?></b></span></div>
<div class="module-management"><?php foreach($catalog as $id=>$m):$online=module_installed($id);?><section class="manage-card" id="<?=esc($id)?>"><img src="assets/icons/<?=esc($id)?>.png" alt=""><div class="manage-info"><h3><?=esc($m['title'])?> <span class="version">v<?=esc($m['version'])?></span></h3><p><?=esc($m['description'])?></p><small class="muted"><?=esc($m['category'])?> · <?=!empty($m['native'])?'Ferramenta JavaScript incluída':'Editor WebAssembly incluído'?></small></div><div class="manage-action"><span class="chip <?=$online?'good':'warn'?>"><?=$online?'Disponível':'Arquivos ausentes'?></span><?php if($online):?><a href="<?=esc(navhref($id))?>" class="btn small subtle">Abrir</a><?php endif;?></div></section><?php endforeach;?></div>
<div class="info-strip small-text">Se algum editor aparecer como <b>Arquivos ausentes</b>, reenvie todos os arquivos do pacote para a pasta correta. O ZIP já contém os motores web compilados; não utilize os ZIPs de código-fonte.</div>
</main>
<?php elseif($page==='files'): ?>
<main class="page-pad"><div class="page-head"><div><div class="eyebrow">SEU WORKSPACE</div><h1>Biblioteca de arquivos</h1><p class="muted">Guarde e baixe arquivos de trabalho. Abra-os manualmente no editor desejado.</p></div></div><div class="files-wrap"><div class="files-intro"><h2>Adicionar arquivos</h2><p>Estes arquivos são armazenados na área privada do servidor. A biblioteca não sincroniza automaticamente os documentos abertos nos editores WASM.</p><form action="files.php" method="post" enctype="multipart/form-data" class="upload-form"><input type="hidden" name="csrf" value="<?=esc(token())?>"><input type="hidden" name="action" value="upload"><input type="file" name="document" required><button class="btn primary" type="submit">Enviar arquivo</button></form><?php if(isset($_GET['result'])):?><div class="alert <?=($_GET['result']==='ok'?'good':'error')?>"><?=esc((string)($_SESSION['files_message']??''))?></div><?php endif;?></div>
<div class="file-table"><h2>Arquivos armazenados</h2><?php $storage=PRIVATE_ROOT.'/file-index.json';$fileIndex=is_file($storage)?(json_decode((string)file_get_contents($storage),true)?:[]):[];if(!$fileIndex):?><div class="empty-files">Nenhum arquivo na biblioteca.</div><?php else:?><table><thead><tr><th>Nome</th><th>Tamanho</th><th>Enviado em</th><th>Ações</th></tr></thead><tbody><?php foreach(array_reverse($fileIndex,true) as $key=>$f):?><tr><td><?=esc((string)$f['name'])?></td><td><?=number_format(($f['size']??0)/1048576,2,',','.')?> MB</td><td><?=esc((string)$f['created'])?></td><td><a class="text-link" href="files.php?get=<?=esc((string)$key)?>">Baixar</a> <form action="files.php" method="post" class="inline-form" onsubmit="return confirm('Excluir permanentemente este arquivo?')"><input type="hidden" name="csrf" value="<?=esc(token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=esc((string)$key)?>"><button type="submit" class="text-link danger">Excluir</button></form></td></tr><?php endforeach;?></tbody></table><?php endif;?></div></div></main>
<?php else: ?>
<main class="page-pad home-page"><div class="hero"><div><div class="eyebrow"><span class="live-dot"></span> SEU ESPAÇO DE CRIAÇÃO</div><h1>Suas ideias começam<br><span>por aqui.</span></h1><p>Da primeira ideia à entrega final. Todas as suas ferramentas criativas em um só lugar.</p><div class="hero-actions"><a class="btn white" href="#catalogo">Explorar ferramentas <span>↗</span></a><a class="btn ghost-light" href="index.php?page=files">Meus arquivos</a></div></div><div class="hero-ornament"><div class="hero-disc">✳</div><span class="orbit orbit-one"></span><span class="orbit orbit-two"></span></div></div>
<div class="metrics"><div><span>Ferramentas disponíveis</span><strong><?=$modulesTotal?></strong></div><div><span>Prontas para uso</span><strong><?=$installed?></strong></div><div><span>Categorias criativas</span><strong>4</strong></div><div><span>Estrutura</span><strong class="small-metric">PHP + WASM</strong></div></div>
<div class="section-head" id="catalogo"><div><div class="eyebrow">EXPERIMENTE / CRIE / EXPORTE</div><h2>Ferramentas criativas</h2><p>Escolha o editor certo para seu projeto.</p></div><div class="search-wrap"><span>⌕</span><input id="tool-search" type="search" autocomplete="off" placeholder="Buscar uma ferramenta..."></div></div>
<div class="filters" id="category-filters"><button type="button" class="filter active" data-cat="Todas">Todas as ferramentas</button><?php foreach(['Documentos','Design','Vídeo e áudio','3D e materiais'] as $cat):?><button type="button" class="filter" data-cat="<?=esc($cat)?>"><?=esc($cat)?></button><?php endforeach;?></div>
<div class="tool-grid" id="tool-grid"><?php foreach($catalog as $id=>$m):$online=module_installed($id);?><a class="tool-card" href="<?=esc(navhref($id))?>" data-title="<?=esc(strtolower($m['title'].' '.$m['subtitle'].' '.$m['description']))?>" data-category="<?=esc($m['category'])?>"><div class="tool-card-top"><div class="tool-image"><img src="assets/icons/<?=esc($id)?>.png" alt="" loading="lazy"></div><span class="tool-card-go">↗</span></div><div class="tool-name"><?=esc($m['title'])?></div><div class="tool-description"><?=esc($m['subtitle'])?></div><div class="tool-card-foot"><span class="category-label"><?=esc($m['category'])?></span><span class="status-text <?=$online?'available':''?>"><i></i><?=$online?'Disponível':'Ausente'?></span></div></a><?php endforeach;?></div><div id="no-results" class="no-results" hidden>Nenhuma ferramenta corresponde à sua pesquisa.</div>
<footer class="footer">Craft Suite · Distribuição web autônoma · Aplicativos originais: ArtCraft / StoryTold (licenças MIT ou Apache-2.0). <a href="index.php?page=manage">Componentes</a></footer></main>
<?php endif;?></div></div><div class="sidebar-overlay" id="sidebar-overlay"></div>
<script src="assets/app.js?v=2.0.0"></script>
<?php endif; ?>
</body></html>
