<?php

/**
 * .env dosyasındaki yapılandırma değişkenlerini yönetir.
 */
class ConfigManager
{
    /**
     * @var array<string, mixed> Yüklenen .env değişkenlerini tutan statik dizi.
     */
    private static $env = [];

    /**
     * .env dosyasını okur ve değişkenleri PHP ortamına yükler.
     *
     * @param string $path .env dosyasının bulunduğu dizinin yolu.
     * @return void
     * @throws \Exception .env dosyası bulunamaz veya okunamzsa.
     */
    public static function load(string $path): void
    {
        if (!empty(self::$env)) {
            return; // Zaten yüklendiyse tekrar yükleme.
        }

        $envFile = rtrim($path, '/') . '/.env';

        if (!is_readable($envFile)) {
            throw new \Exception(".env dosyası bulunamadı veya okunabilir değil: " . $envFile);
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Değer tırnak içindeyse tırnakları kaldır
            if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
                $value = substr($value, 1, -1);
            }
            self::$env[$name] = $value;
        }
    }

    /**
     * Yüklenmiş bir .env değişkeninin değerini döndürür.
     *
     * @param string $key      Aranan değişkenin anahtarı.
     * @param mixed  $default  Anahtar bulunamazsa döndürülecek varsayılan değer.
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return self::$env[$key] ?? $default;
    }
}
