<?php
/**
 * rate_limit.php — rate limiting simples por IP baseado em arquivo.
 * Mesmo mecanismo usado no login (index.php): janela fixa, IP hasheado
 * (nunca armazenado em claro), arquivos em sys_get_temp_dir().
 *
 * Uso:
 *   $rl = rl_status('cadastro', 5, 3600);   // bucket, máx tentativas, janela (s)
 *   if ($rl['bloqueado']) { ... }
 *   rl_register('cadastro', 3600);           // registra uma tentativa
 *   rl_reset('cadastro');                    // zera após sucesso (opcional)
 */

function rl_file(string $bucket): string {
    $ipRaw  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip     = trim(explode(',', $ipRaw)[0]);
    $ipHash = hash('sha256', $bucket . '|' . $ip);
    $dir    = sys_get_temp_dir() . '/fiadoapp_rl';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    return $dir . '/' . $ipHash . '.json';
}

function rl_read(string $bucket, int $janela): array {
    $agora = time();
    $data  = ['tentativas' => 0, 'primeira' => $agora];
    $file  = rl_file($bucket);
    if (file_exists($file)) {
        $raw = @json_decode(file_get_contents($file), true);
        if (is_array($raw)) $data = $raw;
    }
    if (($agora - (int)($data['primeira'] ?? $agora)) > $janela) {
        $data = ['tentativas' => 0, 'primeira' => $agora];
    }
    return $data;
}

function rl_status(string $bucket, int $max, int $janela): array {
    $agora = time();
    $data  = rl_read($bucket, $janela);
    $bloqueado = (int)$data['tentativas'] >= $max;
    $restante  = max(0, $janela - ($agora - (int)$data['primeira']));
    return ['bloqueado' => $bloqueado, 'segundos_restantes' => $restante];
}

function rl_register(string $bucket, int $janela): void {
    $data = rl_read($bucket, $janela);
    $data['tentativas'] = (int)$data['tentativas'] + 1;
    if ($data['tentativas'] === 1) $data['primeira'] = time();
    @file_put_contents(rl_file($bucket), json_encode($data), LOCK_EX);
}

function rl_reset(string $bucket): void {
    @unlink(rl_file($bucket));
}
