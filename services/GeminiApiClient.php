<?php

/**
 * Google Gemini API'sine istekleri gönderen istemci sınıfı.
 */
class GeminiApiClient
{
    /**
     * @var string Kullanılacak Gemini modelinin versiyonu.
     */
    private $geminiVersion = [
        0 => 'gemini-2.5-flash-preview-05-20',
        1 => 'gemini-2.5-flash-preview-native-audio-dialog',
        2 => 'gemini-2.5-flash-exp-native-audio-thinking-dialog',
        3 => 'gemini-2.5-flash-preview-tts',
        4 => 'gemini-2.5-pro-preview-06-05',
        5 => 'gemini-2.5-pro-preview-tts',
        6 => 'gemini-2.0-flash',
        7 => 'gemini-2.0-flash-preview-image-generation',
        8 => 'gemini-2.0-flash-lite',
        9 => 'gemini-1.5-flash',
        10 => 'gemini-1.5-flash-8b',
        11 => 'gemini-1.5-pro',
        12 => 'gemini-embedding-exp',
        13 => 'imagen-3.0-generate-002',
        14 => 'veo-2.0-generate-001',
        15 => 'gemini-2.0-flash-live-001',
    ];

    /**
     * @var string Google AI Studio'dan alınan API anahtarı.
     */
    private $apiKey;

    /**
     * @var string API'nin tam uç noktası.
     */
    private $apiEndpoint = '';
    
    /**
     * @var LoggerService Loglama servisi nesnesi.
     */
    private $logger;

    /**
     * GeminiApiClient constructor.
     *
     * @param string $apiKey Google AI Studio'dan alınan API anahtarı.
     */
    public function __construct(string $apiKey, LoggerService $logger)
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException("API anahtarı boş olamaz.");
        }
        $this->apiKey = $apiKey;
        $this->apiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->geminiVersion[0] . ':generateContent?key=';
        $this->logger = $logger;
        $this->logger->log("API Model: {$this->geminiVersion[0]}", 'INFO');
    }

    /**
     * Gemini API'sine bir istek gönderir ve yanıtı dizi olarak döndürür.
     *
     * @param array $payload API'ye gönderilecek veri gövdesi.
     * @return array|null Başarılı olursa API'den dönen yanıt, aksi halde null.
     * @throws \Exception cURL hatası veya API'den gelen bir hata durumunda.
     */
    public function sendRequest(array $payload): ?array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Bağlantı zaman aşımı
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);      // Toplam işlem zaman aşımı (5 dakika)

        // GÜVENLİK: SSL doğrulaması güvenlik için önemlidir.
        // Yerel sunucuda sorun yaşarsanız geçici olarak false yapabilirsiniz.
        // Ancak canlı ortamda mutlaka 'true' olmalıdır.
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        $this->logger->log($response, 'INFO');
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL Hatası: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \Exception("API'den hata kodu döndü: {$httpCode}. Yanıt: " . $response);
        }

        return json_decode($response, true);
    }
}
