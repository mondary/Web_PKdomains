<?php

$GLOBALS["mail_last_error"] = "";

function mail_last_error(): string {
    return (string)($GLOBALS["mail_last_error"] ?? "");
}

function set_mail_error(string $msg): void {
    $GLOBALS["mail_last_error"] = $msg;
}

function smtp_read_line($fp): string {
    $line = "";
    while (!feof($fp)) {
        $chunk = fgets($fp, 515);
        if ($chunk === false) break;
        $line .= $chunk;
        if (preg_match('/^\d{3} /', $chunk)) {
            break;
        }
    }
    return $line;
}

function smtp_send($fp, string $cmd, int $expect = 250): bool {
    if ($cmd !== "") {
        fwrite($fp, $cmd . "\r\n");
    }
    $resp = smtp_read_line($fp);
    if ($resp === "") {
        set_mail_error("SMTP no response for command: {$cmd}");
        return false;
    }
    $code = (int)substr($resp, 0, 3);
    if ($code !== $expect) {
        set_mail_error("SMTP error {$code} for {$cmd}: {$resp}");
        return false;
    }
    return true;
}

function smtp_send_mail(array $config, string $to, string $from, string $subject, string $message): bool {
    $host = $config["smtp_host"] ?? "";
    $port = (int)($config["smtp_port"] ?? 587);
    $user = $config["smtp_user"] ?? "";
    $pass = $config["smtp_pass"] ?? "";
    $secure = strtolower((string)($config["smtp_secure"] ?? "starttls"));

    if ($host === "" || $user === "" || $pass === "") {
        set_mail_error("SMTP credentials missing");
        return false;
    }

    $fp = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 20);
    if (!$fp) {
        set_mail_error("SMTP connect failed: {$errstr}");
        return false;
    }
    stream_set_timeout($fp, 20);

    $greet = smtp_read_line($fp);
    if ($greet === "") {
        set_mail_error("SMTP no greeting");
        fclose($fp);
        return false;
    }

    if (!smtp_send($fp, "EHLO localhost", 250)) {
        fclose($fp);
        return false;
    }

    if ($secure === "starttls") {
        if (!smtp_send($fp, "STARTTLS", 220)) {
            fclose($fp);
            return false;
        }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            set_mail_error("SMTP STARTTLS failed");
            fclose($fp);
            return false;
        }
        if (!smtp_send($fp, "EHLO localhost", 250)) {
            fclose($fp);
            return false;
        }
    }

    if (!smtp_send($fp, "AUTH LOGIN", 334)) {
        fclose($fp);
        return false;
    }
    if (!smtp_send($fp, base64_encode($user), 334)) {
        fclose($fp);
        return false;
    }
    if (!smtp_send($fp, base64_encode($pass), 235)) {
        fclose($fp);
        return false;
    }

    if (!smtp_send($fp, "MAIL FROM:<{$from}>", 250)) {
        fclose($fp);
        return false;
    }
    if (!smtp_send($fp, "RCPT TO:<{$to}>", 250)) {
        fclose($fp);
        return false;
    }

    if (!smtp_send($fp, "DATA", 354)) {
        fclose($fp);
        return false;
    }

    $headers = [];
    $headers[] = "From: {$from}";
    $headers[] = "To: {$to}";
    $headers[] = "Subject: {$subject}";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $body = implode("\r\n", $headers) . "\r\n\r\n" . $message;
    $body = str_replace("\n.", "\n..", $body);

    fwrite($fp, $body . "\r\n.\r\n");
    $resp = smtp_read_line($fp);
    if (!preg_match('/^250 /', $resp)) {
        set_mail_error("SMTP data rejected: {$resp}");
        fclose($fp);
        return false;
    }

    smtp_send($fp, "QUIT", 221);
    fclose($fp);
    return true;
}

function send_mail(array $config, string $to, string $from, string $subject, string $message): bool {
    set_mail_error("");
    $use_smtp = !empty($config["smtp_host"]);
    if ($use_smtp) {
        return smtp_send_mail($config, $to, $from, $subject, $message);
    }
    $headers = "From: {$from}\r\nReply-To: {$from}\r\n";
    $ok = mail($to, $subject, $message, $headers);
    if (!$ok) {
        set_mail_error("mail() failed");
    }
    return $ok;
}
