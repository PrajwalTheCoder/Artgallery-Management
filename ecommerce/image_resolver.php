<?php
/**
 * Image resolver with self-healing DB update.
 * Given a product row (expects keys: id, image, name) it tries to locate the best matching
 * file under /images and returns the relative path (images/filename). If the filename located
 * differs from the DB value, it will UPDATE the products.image column so subsequent loads are fast.
 *
 * Heuristics order:
 * 1. Exact existing path (as stored).
 * 2. Case-insensitive exact filename match.
 * 3. Match any candidate whose base filename (without final extension) contains any token from the stored base.
 * 4. Fuzzy token overlap scoring (common tokens from dash/underscore split).
 * 5. Fallback placeholder.
 */

function resolve_product_image(?PDO $pdo, array $product): string {
    $raw = trim((string)($product['image'] ?? ''));
    $imagesDir = __DIR__ . '/images';
    $placeholder = 'images/placeholder.svg';
    if (!is_dir($imagesDir)) return $placeholder;

    // If raw appears valid, try direct file first.
    if ($raw !== '') {
        $direct = $imagesDir . '/' . $raw;
        if (is_file($direct)) return 'images/' . basename($direct); // fast path
    }

    // Build candidate list once per request and cache.
    static $candidates = null; static $lowerMap = null; static $tokenIndex = null;
    if ($candidates === null) {
        $candidates = []; $lowerMap = []; $tokenIndex = [];
        foreach (glob($imagesDir . '/*') as $f) {
            if (!is_file($f)) continue;
            $bn = basename($f);
            if ($bn === 'placeholder.svg') continue; // skip placeholder for matching
            $candidates[] = $bn;
            $lowerMap[strtolower($bn)] = $bn;
            $base = preg_replace('/\.[^.]+$/','',$bn); // remove only final extension
            $tokens = preg_split('/[-_\.]+/', strtolower($base));
            $tokens = array_filter($tokens, fn($t)=>$t!=='');
            $tokenIndex[$bn] = $tokens;
        }
    }

    // 2. Case-insensitive identical filename
    if ($raw !== '') {
        $lower = strtolower($raw);
        if (isset($lowerMap[$lower])) {
            $found = $lowerMap[$lower];
            maybe_update_image($pdo, $product, $found);
            return 'images/' . $found;
        }
    }

    // Prepare raw tokens and simplified forms for fuzzy matching.
    $rawBase = $raw !== '' ? preg_replace('/\.[^.]+$/','',$raw) : '';
    $rawTokens = $rawBase !== '' ? preg_split('/[-_\.]+/', strtolower($rawBase)) : [];
    $rawTokens = array_filter($rawTokens, fn($t)=>$t!=='');

    // 3 + 4. Token overlap scoring.
    $best = null; $bestScore = 0;
    foreach ($candidates as $cand) {
        $tokens = $tokenIndex[$cand] ?? [];
        if (empty($tokens)) continue;
        $score = 0;
        // direct containment quick win
        if ($rawBase !== '' && stripos($cand, $rawBase) !== false) $score += 50;
        // token overlaps
        foreach ($rawTokens as $rt) {
            foreach ($tokens as $ct) {
                if ($rt === $ct) { $score += 20; continue 2; }
                if (strlen($rt) >= 4 && strpos($ct, $rt) !== false) { $score += 10; }
                elseif (strlen($ct) >= 4 && strpos($rt, $ct) !== false) { $score += 10; }
            }
        }
        // favor shorter candidate for generic names (avoid accidental match) by slight penalty for length
        $score -= (strlen($cand) / 10);
        if ($score > $bestScore) { $bestScore = $score; $best = $cand; }
    }

    if ($best && $bestScore > 0) {
        maybe_update_image($pdo, $product, $best);
        return 'images/' . $best;
    }

    return $placeholder;
}

function maybe_update_image(?PDO $pdo, array $product, string $newFile): void {
    $current = trim((string)($product['image'] ?? ''));
    if ($current === $newFile) return; // already correct
    if (!$pdo) return; // cannot update
    try {
        $stmt = $pdo->prepare('UPDATE products SET image = ? WHERE id = ?');
        $stmt->execute([$newFile, (int)$product['id']]);
    } catch (Throwable $e) {
        // Silent fail; we don't want rendering to break because of update attempt.
    }
}
