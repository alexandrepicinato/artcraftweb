<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
try{
    require_admin();
    $indexFile=PRIVATE_ROOT.'/file-index.json';
    $records=is_file($indexFile)?(json_decode((string)file_get_contents($indexFile),true)?:[]):[];
    if($_SERVER['REQUEST_METHOD']==='GET'&&isset($_GET['get'])){
        $id=(string)$_GET['get'];if(!preg_match('/^[a-f0-9]{32}$/',$id)||!isset($records[$id]))throw new RuntimeException('Arquivo não encontrado.');
        $file=PRIVATE_ROOT.'/files/'.$id.'.data';if(!is_file($file))throw new RuntimeException('Arquivo não encontrado no disco.');
        $name=str_replace(["\r","\n","\"","\\"],'_',basename((string)$records[$id]['name']));
        header('Content-Type: application/octet-stream');
        header('Content-Length: '.filesize($file));
        header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($file);exit;
    }
    if($_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('Método inválido.');
    validate_token();$action=(string)($_POST['action']??'');
    $lock=fopen(PRIVATE_ROOT.'/files.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Não foi possível bloquear a biblioteca.');
    try{
        $records=is_file($indexFile)?(json_decode((string)file_get_contents($indexFile),true)?:[]):[];
        if($action==='upload'){
            $file=$_FILES['document']??null;
            if(!$file||$file['error']!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']))throw new RuntimeException('Falha no upload. Confira os limites de tamanho do PHP.');
            if($file['size']>200*1048576)throw new RuntimeException('O limite da biblioteca é 200 MB por arquivo.');
            $name=trim(basename(str_replace('\\','/',(string)$file['name'])));
            $name=preg_replace('/[\x00-\x1f\x7f]/u','',$name);
            if(!$name||strlen($name)>220)throw new RuntimeException('Nome de arquivo inválido.');
            if(!is_dir(PRIVATE_ROOT.'/files')&&!mkdir(PRIVATE_ROOT.'/files',0700,true))throw new RuntimeException('Pasta de arquivos indisponível.');
            $id=bin2hex(random_bytes(16));$dest=PRIVATE_ROOT.'/files/'.$id.'.data';
            if(!move_uploaded_file($file['tmp_name'],$dest))throw new RuntimeException('Erro ao armazenar arquivo.');
            chmod($dest,0600);
            $records[$id]=['name'=>$name,'size'=>$file['size'],'created'=>date('d/m/Y H:i')];
            $_SESSION['files_message']='Arquivo salvo com sucesso.';
        }elseif($action==='delete'){
            $id=(string)($_POST['id']??'');
            if(!preg_match('/^[a-f0-9]{32}$/',$id)||!isset($records[$id]))throw new RuntimeException('Arquivo não encontrado.');
            @unlink(PRIVATE_ROOT.'/files/'.$id.'.data');unset($records[$id]);
            $_SESSION['files_message']='Arquivo excluído.';
        }else throw new RuntimeException('Ação inválida.');
        $temp=$indexFile.'.tmp';if(file_put_contents($temp,json_encode($records,JSON_UNESCAPED_UNICODE),LOCK_EX)===false||!rename($temp,$indexFile))throw new RuntimeException('Erro ao salvar o índice.');
        chmod($indexFile,0600);
    }finally{flock($lock,LOCK_UN);fclose($lock);}
    header('Location: index.php?page=files&result=ok',true,303);exit;
}catch(Throwable $e){$_SESSION['files_message']=$e->getMessage();header('Location: index.php?page=files&result=error',true,303);exit;}
