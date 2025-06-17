<?php

/**
 * Dosyaya loglama işlemleri yapan basit bir servis sınıfı.
 */
class LoggerService
{
    /**
     * @var string Log dosyalarının saklanacağı dizin.
     */
    private $logDirectory;

    /**
     * LoggerService constructor.
     *
     * @param string $logDirectory Log dosyalarının bulunacağı dizin yolu.
     * @throws \Exception Log dizini oluşturulamazsa.
     */
    public function __construct(string $logDirectory)
    {
        $this->logDirectory = $logDirectory;
        if (!is_dir($this->logDirectory)) {
            if (!mkdir($this->logDirectory, 0777, true)) {
                // Bu hatayı doğrudan göstermek yerine bir istisna fırlatmak daha doğru
                throw new \Exception("Log dizini oluşturulamadı: " . $this->logDirectory);
            }
        }
    }

    /**
     * Belirtilen seviyede bir log mesajı kaydeder.
     *
     * @param string $message   Kaydedilecek mesaj.
     * @param string $level     Log seviyesi (INFO, WARNING, ERROR).
     * @return void
     */
    public function log(string $message, string $level = 'INFO'): void
    {
        // Günün tarihine göre bir log dosyası adı oluştur
        $logFile = $this->logDirectory . '/' . date('Y-m-d') . '.log';
        
        // Log mesajının formatını oluştur
        $logEntry = sprintf(
            "[%s] [%s]: %s\n",
            date('Y-m-d H:i:s'), // Zaman damgası
            strtoupper($level),   // Log seviyesi
            $message              // Asıl mesaj
        );

        // Mesajı log dosyasına ekle (FILE_APPEND ile üzerine yazmayı engelle)
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
