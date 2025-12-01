<?php
class Session {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            // Seguridad básica
            session_start([
                'cookie_httponly' => true,
                'cookie_secure'   => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Lax'
            ]);
        }

        // Flashdata: mover __flash_temp a __flash
        if (isset($_SESSION['__flash_temp'])) {
            $_SESSION['__flash'] = $_SESSION['__flash_temp'];
            unset($_SESSION['__flash_temp']);
        } else {
            $_SESSION['__flash'] = [];
        }
    }

    // -----------------------------
    // Set / Get
    // -----------------------------

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public function has($key) {
        return isset($_SESSION[$key]);
    }

    public function unset($key) {
        unset($_SESSION[$key]);
    }

    public function destroy() {
        session_destroy();
    }

    // -----------------------------
    // Flashdata estilo CI3
    // -----------------------------

    public function set_flashdata($key, $value) {
        $_SESSION['__flash_temp'][$key] = $value;
    }

    public function flashdata($key) {
        return $_SESSION['__flash'][$key] ?? null;
    }
}