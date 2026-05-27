<?php
$path = __DIR__ . '/../includes/catalog_store.php';
if (!is_readable($path)) { echo "Cannot read $path\n"; exit(2); }
$code = file_get_contents($path);
$tokens = token_get_all($code);
$line = 1;
$open = 0;
$first_unbalanced = null;
foreach ($tokens as $tok) {
    if (is_array($tok)) {
        $token_name = token_name($tok[0]);
        $token_text = $tok[1];
        $lines = substr_count($token_text, "\n");
        // skip strings and comments from counting
        if (in_array($tok[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE])) {
            $line += $lines;
            continue;
        }
        // count braces inside token text (rare)
        $pos = 0;
        while (($p = strpos($token_text, '{', $pos)) !== false) { $open++; $pos = $p + 1; }
        $pos = 0;
        while (($p = strpos($token_text, '}', $pos)) !== false) { $open--; if ($open < 0 && $first_unbalanced === null) { $first_unbalanced = $line; } $pos = $p + 1; }
        $line += $lines;
    } else {
        // simple character token like '{' or '}' or ';' or "\n"
        if ($tok === "\n") { $line++; continue; }
        if ($tok === '{') { $open++; }
        if ($tok === '}') { $open--; if ($open < 0 && $first_unbalanced === null) { $first_unbalanced = $line; } }
    }
}
if ($open !== 0) {
    echo "Unmatched braces: open_count=$open\n";
    if ($first_unbalanced !== null) echo "First unbalanced at approx line: $first_unbalanced\n";
    else echo "No early negative balance detected; total opens > closes.\n";
    exit(1);
}
echo "Braces balanced.\n";
exit(0);
